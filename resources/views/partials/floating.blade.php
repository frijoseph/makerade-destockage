@if ($shop->whatsapp_float_enabled && $shop->whatsapp_number)
    <a href="https://wa.me/{{ $shop->whatsapp_number }}?text={{ rawurlencode($shop->text('whatsapp_message')) }}" class="wa-float" target="_blank" rel="noopener" aria-label="Discuter sur WhatsApp">
        <x-icon name="chat" :size="26" :stroke="1.8" />
        <span class="wa-tip">Une question ? Écrivez-nous</span>
    </a>
@endif
