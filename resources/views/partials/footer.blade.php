<footer class="mt-16 border-t border-line bg-soft">
    <div class="container-x grid gap-10 py-12 md:grid-cols-2 lg:grid-cols-4">
        <div>
            <x-logo class="h-9 w-auto"/>
            <p class="mt-3 text-sm font-medium text-navy">{{ setting('tagline', 'Everything Is Possible.') }}</p>
            <p class="mt-2 max-w-xs text-sm text-muted">Quality household, kitchen, cleaning, personal care and lifestyle products delivered across Pakistan.</p>
            <div class="mt-4 flex gap-2">
                @foreach(['facebook' => 'M14 8h3V4h-3a4 4 0 00-4 4v2H7v4h3v8h4v-8h3l1-4h-4V8a1 1 0 011-1z', 'instagram' => 'M7 3h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4V7a4 4 0 014-4zm5 5a4 4 0 100 8 4 4 0 000-8zm5.500-1.500h.01', 'tiktok' => 'M14 3v11.500a3.500 3.500 0 11-3.500-3.500M14 3c.3 2.500 2 4.200 5 4.500'] as $net => $path)
                    @if($u = setting('social_' . $net))<a href="{{ $u }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($net) }}" class="grid h-9 w-9 place-items-center rounded-full border border-line bg-white hover:border-navy"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}"/></svg></a>@endif
                @endforeach
            </div>
        </div>
        <div>
            <h3 class="mb-3 text-sm uppercase tracking-wide">Shop</h3>
            <ul class="space-y-2 text-sm text-muted">@foreach($footerShop as $i)<li><a href="{{ $i->href }}" class="hover:text-navy">{{ $i->label }}</a></li>@endforeach</ul>
        </div>
        <div>
            <h3 class="mb-3 text-sm uppercase tracking-wide">Help</h3>
            <ul class="space-y-2 text-sm text-muted">@foreach($footerHelp as $i)<li><a href="{{ $i->href }}" class="hover:text-navy">{{ $i->label }}</a></li>@endforeach<li><a href="{{ route('blog') }}" class="hover:text-navy">Blog</a></li></ul>
        </div>
        <div>
            <h3 class="mb-3 text-sm uppercase tracking-wide">Contact</h3>
            <ul class="space-y-2 text-sm text-muted">
                @if(setting('contact_email'))<li><a href="mailto:{{ setting('contact_email') }}" class="hover:text-navy">{{ setting('contact_email') }}</a></li>@endif
                @if(setting('contact_phone'))<li><a href="tel:{{ setting('contact_phone') }}" class="hover:text-navy">{{ setting('contact_phone') }}</a></li>@endif
                @if(setting('whatsapp_number'))<li><a href="https://wa.me/{{ preg_replace('/\D/', '', setting('whatsapp_number')) }}" target="_blank" rel="noopener" class="hover:text-navy">WhatsApp us</a></li>@endif
                @if(setting('contact_address'))<li>{{ setting('contact_address') }}</li>@endif
            </ul>
            <p class="mt-4 text-xs text-muted">We accept: {{ setting('payment_info', 'Cash on Delivery · Bank Transfer') }}</p>
        </div>
    </div>
    <div class="border-t border-line py-4 text-center text-xs text-muted">© {{ date('Y') }} {{ setting('site_name', 'Turtle Boss') }}. All rights reserved.</div>
</footer>
