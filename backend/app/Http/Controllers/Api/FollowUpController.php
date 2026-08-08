<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DomainRuleViolation;
use App\Http\Controllers\Controller;
use App\Models\AggregateSnapshot;
use App\Models\Finding;
use App\Models\FollowUpAction;
use App\Models\User;
use App\Services\FollowUpWorkflow;
use App\Services\OrganizationalScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FollowUpController extends Controller
{
    public function assignees(Request $request, OrganizationalScope $scope): JsonResponse
    {
        $validated = $request->validate(['unit_id' => ['required', 'uuid', 'exists:organizational_units,id']]);
        if (! $scope->allows($request->user(), $validated['unit_id'])) {
            throw new DomainRuleViolation('forbidden', 'Unit berada di luar scope Anda.', 403);
        }
        $users = User::query()->where('is_active', true)->whereHas('organizationalUnits', fn ($query) => $query->where('organizational_units.id', $validated['unit_id']))->get();

        return response()->json(['data' => $users->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name, 'can_update' => $user->can('action.update'), 'can_verify' => $user->can('action.verify')])->filter(fn (array $user) => $user['can_update'] || $user['can_verify'])->values()]);
    }

    public function index(Request $request, OrganizationalScope $scope): JsonResponse
    {
        $query = Finding::with(['ownerUnit', 'actions.pic', 'actions.verifier'])->whereIn('owner_unit_id', $scope->accessibleUnitIds($request->user()));
        if ($request->string('state')->isNotEmpty()) {
            $query->where('state', $request->string('state')->toString());
        }

        return response()->json(['data' => $query->latest()->get()->map(fn ($finding) => $this->findingData($finding))]);
    }

    public function create(Request $request, OrganizationalScope $scope): JsonResponse
    {
        $validated = $request->validate(['source_type' => ['required', 'in:manual,low_indicator'], 'aggregate_snapshot_id' => ['nullable', 'uuid', 'exists:aggregate_snapshots,id'], 'owner_unit_id' => ['required', 'uuid', 'exists:organizational_units,id'], 'source_indicator_code' => ['nullable', 'string', 'max:80'], 'title' => ['required', 'string', 'max:300'], 'description' => ['required', 'string', 'max:8000'], 'source_evidence' => ['required', 'string', 'max:8000'], 'severity' => ['required', 'in:low,medium,high,critical'], 'due_on' => ['required', 'date', 'after_or_equal:today']]);
        if (! $scope->allows($request->user(), $validated['owner_unit_id'])) {
            throw new DomainRuleViolation('forbidden', 'Unit finding berada di luar scope Anda.', 403);
        }
        $snapshot = isset($validated['aggregate_snapshot_id']) ? AggregateSnapshot::findOrFail($validated['aggregate_snapshot_id']) : null;
        if ($snapshot && ($snapshot->state !== 'released' || $snapshot->owner_unit_id !== $validated['owner_unit_id'])) {
            throw new DomainRuleViolation('invalid_source', 'Snapshot harus released dan berada dalam unit finding.', 422);
        }
        $score = null;
        if ($validated['source_type'] === 'low_indicator') {
            if (! $snapshot || $snapshot->state !== 'released' || $snapshot->owner_unit_id !== $validated['owner_unit_id']) {
                throw new DomainRuleViolation('invalid_source', 'Finding indikator rendah memerlukan snapshot released dalam unit yang sama.', 422);
            }
            $indicator = collect($snapshot->metrics['indicators'] ?? [])->firstWhere('code', $validated['source_indicator_code'] ?? null);
            if (! $indicator || ($indicator['suppressed'] ?? true) || ($indicator['normalized_score'] ?? 100) >= 60) {
                throw new DomainRuleViolation('invalid_source', 'Indikator tidak tersedia, suppressed, atau tidak berada pada band rendah.', 422);
            }
            $score = $indicator['normalized_score'];
        }
        $finding = Finding::create($validated + ['aggregate_snapshot_id' => $snapshot?->id, 'source_score' => $score, 'code' => 'FND-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)), 'created_by' => $request->user()->id]);
        activity('follow_up')->performedOn($finding)->causedBy($request->user())->withProperties(['source_type' => $finding->source_type, 'owner_unit_id' => $finding->owner_unit_id, 'snapshot_id' => $finding->aggregate_snapshot_id])->log('finding_created');

        return response()->json(['data' => $this->findingData($finding->load(['ownerUnit', 'actions']))], 201);
    }

    public function show(Request $request, Finding $finding, OrganizationalScope $scope): JsonResponse
    {
        $this->assertScope($request, $finding, $scope);

        return response()->json(['data' => $this->findingData($finding->load(['ownerUnit', 'actions.pic', 'actions.verifier', 'actions.evidence', 'actions.verifications']))]);
    }

    public function update(Request $request, Finding $finding, OrganizationalScope $scope): JsonResponse
    {
        $this->assertScope($request, $finding, $scope);
        $this->assertVersion($request, $finding->resource_version);
        if (! in_array($finding->state, ['open', 'in_progress'], true)) {
            throw new DomainRuleViolation('invalid_transition', 'Finding pada status ini tidak dapat diubah.', 409);
        }
        $validated = $request->validate(['title' => ['sometimes', 'string', 'max:300'], 'description' => ['sometimes', 'string', 'max:8000'], 'severity' => ['sometimes', 'in:low,medium,high,critical'], 'due_on' => ['sometimes', 'date', 'after_or_equal:today'], 'due_date_reason' => ['required_with:due_on', 'string', 'min:3', 'max:1000']]);
        $finding->update(collect($validated)->except('due_date_reason')->all() + ['resource_version' => $finding->resource_version + 1]);
        activity('follow_up')->performedOn($finding)->causedBy($request->user())->withProperties(['version' => $finding->resource_version, 'due_date_reason' => $validated['due_date_reason'] ?? null])->log('finding_updated');

        return response()->json(['data' => $this->findingData($finding)]);
    }

    public function createAction(Request $request, Finding $finding, OrganizationalScope $scope, FollowUpWorkflow $workflow): JsonResponse
    {
        $this->assertScope($request, $finding, $scope);
        $validated = $request->validate(['pic_user_id' => ['required', 'integer', 'exists:users,id'], 'verifier_user_id' => ['required', 'integer', 'different:pic_user_id', 'exists:users,id'], 'title' => ['required', 'string', 'max:300'], 'root_cause' => ['required', 'string', 'max:8000'], 'plan' => ['required', 'string', 'max:12000'], 'expected_output' => ['required', 'string', 'max:4000'], 'resource_needs' => ['nullable', 'string', 'max:4000'], 'due_on' => ['required', 'date', 'after_or_equal:today']]);
        $pic = User::findOrFail($validated['pic_user_id']);
        $verifier = User::findOrFail($validated['verifier_user_id']);
        if (! $scope->allows($pic, $finding->owner_unit_id) || ! $scope->allows($verifier, $finding->owner_unit_id) || ! $pic->can('action.update') || ! $verifier->can('action.verify')) {
            throw new DomainRuleViolation('invalid_assignment', 'PIC dan verifier harus memiliki role dan assignment yang sesuai pada unit finding.', 422);
        }
        $action = $workflow->createAction($finding, $pic, $verifier, collect($validated)->except(['pic_user_id', 'verifier_user_id'])->all(), $request->user());

        return response()->json(['data' => $this->actionData($action->load(['pic', 'verifier', 'evidence', 'verifications']))], 201);
    }

    public function showAction(Request $request, FollowUpAction $followUpAction, OrganizationalScope $scope): JsonResponse
    {
        $this->assertScope($request, $followUpAction->finding, $scope);

        return response()->json(['data' => $this->actionData($followUpAction->load(['finding', 'pic', 'verifier', 'evidence', 'verifications']))]);
    }

    public function updateAction(Request $request, FollowUpAction $followUpAction, OrganizationalScope $scope, FollowUpWorkflow $workflow): JsonResponse
    {
        $this->assertScope($request, $followUpAction->finding, $scope);
        $this->assertVersion($request, $followUpAction->resource_version);
        $validated = $request->validate(['state' => ['sometimes', 'in:accepted,rejected,in_progress'], 'rejection_reason' => ['nullable', 'string', 'max:4000'], 'root_cause' => ['sometimes', 'string', 'max:8000'], 'plan' => ['sometimes', 'string', 'max:12000'], 'expected_output' => ['sometimes', 'string', 'max:4000'], 'resource_needs' => ['nullable', 'string', 'max:4000'], 'progress' => ['sometimes', 'integer', 'min:0', 'max:100'], 'due_on' => ['sometimes', 'date', 'after_or_equal:today'], 'due_date_reason' => ['required_with:due_on', 'string', 'min:3', 'max:1000']]);

        return response()->json(['data' => $this->actionData($workflow->updateAction($followUpAction, $request->user(), $validated))]);
    }

    public function evidence(Request $request, FollowUpAction $followUpAction, OrganizationalScope $scope, FollowUpWorkflow $workflow): JsonResponse
    {
        $this->assertScope($request, $followUpAction->finding, $scope);
        $validated = $request->validate(['title' => ['required', 'string', 'max:240'], 'description' => ['required', 'string', 'max:8000'], 'reference_url' => ['nullable', 'url:https', 'max:1000']]);
        $evidence = $workflow->addEvidence($followUpAction, $request->user(), $validated);

        return response()->json(['data' => ['id' => $evidence->id, 'title' => $evidence->title, 'description' => $evidence->description, 'reference_url' => $evidence->reference_url, 'checksum' => $evidence->checksum, 'version' => $evidence->version, 'created_at' => $evidence->created_at?->toIso8601String()]], 201);
    }

    public function submit(Request $request, FollowUpAction $followUpAction, OrganizationalScope $scope, FollowUpWorkflow $workflow): JsonResponse
    {
        $this->assertScope($request, $followUpAction->finding, $scope);
        $this->assertVersion($request, $followUpAction->resource_version);

        return response()->json(['data' => $this->actionData($workflow->submit($followUpAction, $request->user()))]);
    }

    public function verify(Request $request, FollowUpAction $followUpAction, OrganizationalScope $scope, FollowUpWorkflow $workflow): JsonResponse
    {
        $this->assertScope($request, $followUpAction->finding, $scope);
        $this->assertVersion($request, $followUpAction->resource_version);
        $validated = $request->validate(['decision' => ['required', 'in:verified,needs_revision,rejected'], 'reason' => ['required', 'string', 'min:3', 'max:4000'], 'evidence_review' => ['required', 'string', 'min:3', 'max:8000']]);

        return response()->json(['data' => $this->actionData($workflow->verify($followUpAction, $request->user(), $validated['decision'], $validated['reason'], $validated['evidence_review']))]);
    }

    public function dashboard(Request $request, OrganizationalScope $scope): JsonResponse
    {
        $actions = FollowUpAction::with('finding.ownerUnit')->whereHas('finding', fn ($query) => $query->whereIn('owner_unit_id', $scope->accessibleUnitIds($request->user())))->get();
        $states = $actions->countBy('state');

        return response()->json(['data' => ['counts' => $states, 'total' => $actions->count(), 'overdue' => $actions->filter(fn ($action) => $action->due_on->isPast() && ! in_array($action->state, ['verified', 'rejected'], true))->count(), 'pending_verification' => $states->get('pending_verification', 0), 'revision' => $states->get('needs_revision', 0), 'items' => $actions->map(fn ($action) => ['id' => $action->id, 'finding_code' => $action->finding->code, 'title' => $action->title, 'unit' => $action->finding->ownerUnit->name, 'state' => $action->state, 'progress' => $action->progress, 'due_on' => $action->due_on->toDateString(), 'overdue' => $action->due_on->isPast() && ! in_array($action->state, ['verified', 'rejected'], true)])->values()]]);
    }

    private function assertScope(Request $request, Finding $finding, OrganizationalScope $scope): void
    {
        if (! $scope->allows($request->user(), $finding->owner_unit_id)) {
            throw new DomainRuleViolation('forbidden', 'Finding berada di luar scope Anda.', 403);
        }
    }

    private function assertVersion(Request $request, int $current): void
    {
        if (! preg_match('/^(?:W\/)?"(\d+)"$/', (string) $request->header('If-Match'), $matches)) {
            throw new DomainRuleViolation('precondition_required', 'If-Match version diperlukan.', 428);
        }
        if ((int) $matches[1] !== $current) {
            throw new DomainRuleViolation('version_conflict', 'Resource telah berubah.', 412);
        }
    }

    private function findingData(Finding $finding): array
    {
        $finding->loadMissing(['ownerUnit', 'actions.pic', 'actions.verifier']);

        return ['id' => $finding->id, 'code' => $finding->code, 'source_type' => $finding->source_type, 'snapshot_id' => $finding->aggregate_snapshot_id, 'source_indicator_code' => $finding->source_indicator_code, 'source_score' => $finding->source_score === null ? null : (float) $finding->source_score, 'owner_unit_id' => $finding->owner_unit_id, 'unit' => $finding->ownerUnit?->name, 'title' => $finding->title, 'description' => $finding->description, 'source_evidence' => $finding->source_evidence, 'severity' => $finding->severity, 'state' => $finding->state, 'due_on' => $finding->due_on->toDateString(), 'version' => $finding->resource_version, 'actions' => $finding->relationLoaded('actions') ? $finding->actions->map(fn ($action) => $this->actionData($action))->values() : []];
    }

    private function actionData(FollowUpAction $action): array
    {
        $action->loadMissing(['pic', 'verifier']);

        return ['id' => $action->id, 'finding_id' => $action->finding_id, 'title' => $action->title, 'pic' => $action->pic?->only(['id', 'name']), 'verifier' => $action->verifier?->only(['id', 'name']), 'root_cause' => $action->root_cause, 'plan' => $action->plan, 'expected_output' => $action->expected_output, 'resource_needs' => $action->resource_needs, 'assignment_note' => $action->assignment_note, 'state' => $action->state, 'progress' => $action->progress, 'due_on' => $action->due_on->toDateString(), 'revision_count' => $action->revision_count, 'version' => $action->resource_version, 'evidence' => $action->relationLoaded('evidence') ? $action->evidence : [], 'verifications' => $action->relationLoaded('verifications') ? $action->verifications : []];
    }
}
