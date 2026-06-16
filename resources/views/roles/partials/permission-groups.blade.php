@php
    use App\Support\PermissionLabel;

    $checkedIds = collect(old('permissions', $selectedIds->all()))
        ->map(fn ($id) => (int) $id)
        ->unique();
@endphp

<div class="space-y-4">
    @forelse ($permissionsGrouped as $groupKey => $permissions)
        <fieldset
            class="rounded-xl border border-outline-variant/15 bg-surface-container-low/60 p-4 shadow-sm ring-1 ring-outline-variant/5 sm:p-5"
        >
            <legend class="mb-3 w-full px-0.5 font-headline text-xs font-bold uppercase tracking-wider text-primary">
                {{ PermissionLabel::resourceGroupTitle($groupKey) }}
            </legend>
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($permissions as $permission)
                    <label
                        class="group flex cursor-pointer items-start gap-3 rounded-xl border border-outline-variant/10 bg-surface-container-lowest px-3 py-2.5 transition hover:border-primary/25 hover:bg-surface-container-low hover:shadow-sm"
                    >
                        <input
                            type="checkbox"
                            name="permissions[]"
                            value="{{ $permission->id }}"
                            @checked($checkedIds->contains($permission->id))
                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-outline-variant text-primary focus:ring-2 focus:ring-primary/30"
                        />
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-medium text-on-surface">
                                {{ PermissionLabel::for($permission->name) }}
                            </span>
                            <span class="mt-0.5 block truncate font-mono text-[11px] leading-tight text-on-surface-variant">
                                {{ $permission->name }}
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @empty
        <div
            class="rounded-xl border border-dashed border-outline-variant/25 bg-surface-container-low/40 px-6 py-10 text-center ring-1 ring-outline-variant/5"
        >
            <x-lumina.icon name="key_off" class="mx-auto !text-3xl text-on-surface-variant opacity-60" />
            <p class="mt-3 text-sm font-medium text-on-surface">{{ __('No permissions yet') }}</p>
            <p class="mt-1 text-sm text-on-surface-variant">
                {{ __('Create permissions first, then return here to attach them to this role.') }}
            </p>
        </div>
    @endforelse
</div>
