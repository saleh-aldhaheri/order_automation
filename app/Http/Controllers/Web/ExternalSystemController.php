<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ExternalSystem;
use App\Services\ExternalSystemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExternalSystemController extends Controller
{
    public function __construct(
        private ExternalSystemService $externalSystemService
    ) {}

    public function index(Request $request): View
    {
        [$perPage, $perPageOptions] = $this->resolvePagination($request);

        $search = $this->getSearch($request);

        $externalSystems = $this->externalSystemService->getExternalSystems($perPage, $search);

        return view('external-systems.index', [
            'external_systems' => $externalSystems,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'search' => $search ?? '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'system_name' => ['required', 'min:2', 'max:256', 'unique:external_systems,system_name'],
            'is_active' => ['required', 'boolean'],
        ]);

        $result = $this->externalSystemService->storeExternalSystem(
            $validated['system_name'],
            (bool) $validated['is_active']
        );

        return redirect()
            ->route('external-systems.index')
            ->with('success', __('System created.'))
            ->with('copy_client_secret', $result['plain_client_secret']);
    }

    public function update(ExternalSystem $externalSystem, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'system_name' => [
                'required',
                'string',
                'min:2',
                'max:256',
                Rule::unique('external_systems', 'system_name')->ignore($externalSystem->id),
            ],
            'is_active' => ['required', 'boolean'],
        ]);

        $this->externalSystemService->updateExternalSystem(
            $externalSystem,
            $validated['system_name'],
            (bool) $validated['is_active']
        );

        return redirect()->route('external-systems.index')
            ->with('success', __('System updated.'));
    }

    public function generateToken(ExternalSystem $externalSystem): RedirectResponse
    {
        $token = $this->externalSystemService->generateAccessToken($externalSystem);

        return redirect()
            ->route('external-systems.index')
            ->with('success', __('Token generated.'))
            ->with('copy_token', $token);
    }

    public function rotateClientSecret(ExternalSystem $externalSystem): RedirectResponse
    {
        $credentials = $this->externalSystemService->rotateClientSecret($externalSystem);

        return redirect()
            ->route('external-systems.index')
            ->with('success', __('Client secret regenerated.'))
            ->with('copy_client_secret', $credentials['plain_client_secret']);
    }

    public function revokeToken(ExternalSystem $externalSystem): RedirectResponse
    {
        $this->externalSystemService->revokeAllTokens($externalSystem);

        return redirect()->route('external-systems.index')
            ->with('success', __('Token revoked.'));
    }

    public function destroy(ExternalSystem $externalSystem): RedirectResponse
    {
        $this->externalSystemService->deleteExternalSystem($externalSystem);

        return redirect()->route('external-systems.index')
            ->with('success', __('System Deleted.'));
    }
}
