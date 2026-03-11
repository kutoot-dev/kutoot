<?php

namespace App\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class OptimizeServerActions extends Component
{
    public function optimizeClear(): void
    {
        $this->authorize();

        Artisan::call('optimize:clear');

        Notification::make()
            ->title('Optimize:Clear completed')
            ->body('Config, route, view, and cache have been cleared.')
            ->success()
            ->send();
    }

    public function optimize(): void
    {
        $this->authorize();

        Artisan::call('optimize');

        Notification::make()
            ->title('Optimize completed')
            ->body('Application has been optimized and caches rebuilt.')
            ->success()
            ->send();
    }

    protected function authorize(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->hasRole('Super Admin'),
            403,
            'Only Super Admins can run server optimization commands.',
        );
    }

    public function render()
    {
        return view('livewire.optimize-server-actions');
    }
}
