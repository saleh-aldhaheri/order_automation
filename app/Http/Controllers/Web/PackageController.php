<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\PackageService;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function __construct(private PackageService $packageService) {}

    public function index(Request $request)
    {
        [$perPage, $perPageOptions] = $this->resolvePagination($request);

        $search = $this->getSearch($request);

        $packages = $this->packageService->getPackages($perPage, $search);

        return view('packages.index', [
            'packages' => $packages,
            'perPage' => $perPage,
            'perPageOptions' => [5, 10, 25],
            'search' => $search ?? '',
        ]);
    }

    public function show(Package $package)
    {
        $package = $this->packageService->getPackage($package);

        return view('packages.view', [
            'package' => $package,
        ]);
    }

    public function sync(Package $package)
    {
        $this->packageService->syncFromMarketplace($package);

        return back()->with('success', __('Package synced from the marketplace.'));

    }
}
