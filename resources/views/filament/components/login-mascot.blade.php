@if(request()->routeIs('*login*'))
    <!-- Ambient Glowing Orbs -->
    <div class="ambient-glow -top-24 -left-24"></div>
    <div class="ambient-glow -bottom-24 -right-24"></div>

    <!-- Floating Mascot NetSpeed on Top Right of Card -->
    <div class="floating-mascot-login pointer-events-none select-none">
        <img src="{{ asset('images/mascot-wifi-phone.webp') }}" alt="Mascot NetSpeed" class="w-full h-auto object-contain">
    </div>
@endif

