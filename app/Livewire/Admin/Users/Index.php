<?php

namespace App\Livewire\Admin\Users;

use App\Enum\Can;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Collection|User[] $users
 * @property-read array $headers
 */
class Index extends Component
{
    public ?string $search = null;
    public array $search_permissions = [];

    public function mount(): void
    {
        $this->authorize(Can::BE_AN_ADMIN->value);
    }

    public function render():View
    {
        return view('livewire.admin.users.index');
    }

    #[Computed]
    public function users(): Collection
    {
        $this->validate(['search_permissions' => 'exists:permissions,id']);
        
        return User::query()
            ->when(
                $this->search,
                fn (Builder $query) => $query->where(
                    DB::raw('lower(name)'),
                    'like',
                    '%' . strtolower($this->search) . '%'
                )->orWhere(
                    'email',
                    'like',
                    '%' . strtolower($this->search) . '%'
                )
            )
            ->when($this->search_permissions,
                fn (Builder $query) => $query->whereRaw(
                    '(select count(*) from permission_user
                    where permission_id in (?)
                    and user_id = users.id) > 0
                    ', $this->search_permissions
                )
            )
            ->get();
    }

    #[Computed]
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email']
        ];
    }
}
