@props(['metric'])
@php($isCount = ($metric['format'] ?? 'currency') === 'count')
<article {{ $attributes->class(['mc-kpi', 'mc-' . $metric['color']]) }}>
    <div class="mc-kpi-top"><span class="mc-kpi-icon"><x-dynamic-component :component="$metric['icon']" /></span><div><b>{{ $metric['label'] }}</b><strong>{{ $isCount ? number_format((float) $metric['value']) : \App\Filament\Widgets\IndicadoresDashboard::formatCurrency($metric['value']) }}</strong><small @class(['is-down' => ($metric['change'] ?? 0) < 0])>@if(isset($metric['helper'])){{ $metric['helper'] }}@elseif($metric['change'] !== null){{ $metric['change'] >= 0 ? '↑' : '↓' }} {{ abs($metric['change']) }}% vs. periodo anterior @else Sin periodo comparable @endif</small></div></div>
    <div class="mc-kpi-branches">@forelse($metric['branches'] as $branch)<span>{{ $branch['name'] }} <b>{{ $isCount ? number_format((float) $branch['value']) : \App\Filament\Widgets\IndicadoresDashboard::formatCurrency($branch['value']) }}</b></span>@empty<span>Sin sucursales disponibles</span>@endforelse</div>
</article>
