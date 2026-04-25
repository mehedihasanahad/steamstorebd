@props(['card'])

<div class="bg-gray-900 rounded-2xl overflow-hidden border border-gray-700/50 hover:border-brand-500/50 transition-all duration-200 hover:scale-[1.02] hover:shadow-steam-glow group flex flex-col">
    {{-- Gift Card Image or CSS-generated visual --}}
    @if($card->image)
    <div class="relative h-64 overflow-hidden bg-gray-800">
        <img src="{{ Storage::disk('public')->url($card->image) }}" 
             alt="{{ $card->name }}" 
             class="w-full h-full object-cover">
        {{-- Denomination badge --}}
        <div class="absolute top-3 right-3 bg-brand-500 text-white text-xs font-bold px-2 py-1 rounded-lg">
            ${{ number_format($card->denomination_usd, 0) }} USD
        </div>
        {{-- Optional badge --}}
        @if($card->badge_text)
        <div class="absolute top-3 left-3 bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded-lg">
            {{ $card->badge_text }}
        </div>
        @endif
    </div>
    @else
    <div class="card-gradient relative h-40 flex items-center justify-center overflow-hidden">
        <div class="absolute inset-0 opacity-25" style="background: radial-gradient(circle at 30% 50%, #2563EB 0%, transparent 60%);"></div>
        <div class="relative text-center">
            <div class="text-5xl mb-1">🎮</div>
            <div class="text-white font-bold text-lg tracking-widest">STEAM</div>
        </div>
        {{-- Denomination badge --}}
        <div class="absolute top-3 right-3 bg-brand-500 text-white text-xs font-bold px-2 py-1 rounded-lg">
            ${{ number_format($card->denomination_usd, 0) }} USD
        </div>
        {{-- Optional badge --}}
        @if($card->badge_text)
        <div class="absolute top-3 left-3 bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded-lg">
            {{ $card->badge_text }}
        </div>
        @endif
    </div>
    @endif

    <div class="p-4 flex flex-col flex-1">
        <h3 class="font-semibold text-white text-sm mb-1 group-hover:text-brand-400 transition-colors">{{ $card->name }}</h3>
        <p class="text-gray-400 text-xs mb-3">{{ $card->category->name ?? '' }}</p>

        <div class="mt-auto">
            {{-- Price --}}
            <div class="mb-3">
                <span class="text-brand-400 font-bold text-xl">{{ format_bdt($card->price_bdt) }}</span>
                @if($card->price_bdt < $card->denomination_bdt)
                <span class="text-gray-500 text-xs line-through ml-2">{{ format_bdt($card->denomination_bdt) }}</span>
                @endif
            </div>

            {{-- Stock --}}
            <div class="flex items-center gap-1 mb-3">
                @if($card->stock_count > 0)
                    <span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span>
                    <span class="text-green-400 text-xs">In Stock</span>
                @else
                    <span class="w-2 h-2 rounded-full bg-red-400 inline-block"></span>
                    <span class="text-red-400 text-xs">Out of Stock</span>
                @endif
            </div>

            {{-- Buy button --}}
            @if($card->stock_count > 0)
            <form method="POST" action="{{ route('cart.add') }}">
                @csrf
                <input type="hidden" name="gift_card_id" value="{{ $card->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="w-full btn-steam font-semibold py-2.5 rounded-xl text-sm transition-all duration-200 hover:shadow-steam-glow">
                    Buy Now
                </button>
            </form>
            @else
            <button disabled class="w-full bg-gray-700 text-gray-500 font-semibold py-2.5 rounded-xl text-sm cursor-not-allowed">
                Out of Stock
            </button>
            @endif
        </div>
    </div>
</div>
