<?php

namespace App\Filament\Widgets;

use App\Models\AgentTransaction;
use Filament\Widgets\Widget;

class AgentQuickActionsWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    protected string $view = 'filament.widgets.agent-quick-actions-widget';

    public static function canView(): bool
    {
        return auth()->user()?->role === 'agent';
    }

    public function getViewData(): array
    {
        $user = auth()->user();
        $agent = $user?->agent;

        $recentTransactions = collect();
        if ($agent) {
            $recentTransactions = AgentTransaction::where('agent_id', $agent->id)
                ->with(['invoice.customer', 'creator'])
                ->latest()
                ->take(6)
                ->get();
        }

        return [
            'agent' => $agent,
            'recentTransactions' => $recentTransactions,
        ];
    }
}
