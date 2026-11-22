<section class="newsband">
    <img class="nb-leaf l" src="{{ asset('img/fresh/leaf-l.jpg') }}" alt="" loading="lazy">
    <img class="nb-leaf r" src="{{ asset('img/fresh/leaf-r.jpg') }}" alt="" loading="lazy">
    <div class="container newsband-in">
        <div class="nb-txt"><h3>{{ $shop->text('newsletter_title') }}</h3><p>{{ $shop->text('newsletter_text') }}</p></div>
        <form class="news-form" id="newsForm" novalidate>
            <x-phone-input name="phone" id="newsPhone" required />
            <input type="text" name="website" tabindex="-1" autocomplete="off" class="hp" aria-hidden="true">
            <button class="nb-btn">S'abonner <x-icon name="arrow-right" :size="15" :stroke="2.2" /></button>
        </form>
    </div>
</section>

<footer class="fresh-foot">
    <div class="container">
        <div class="ff-cols">
            <div class="ff-brand">
                @include('partials.logo', ['footer' => true])
                <p>{{ $shop->text('shop_tagline') }}</p>
                @if ($socials = $shop->socialLinks())
                    <div class="social">
                        @foreach ($socials as $network => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}"><x-icon :name="$network" :size="20" /></a>
                        @endforeach
                    </div>
                @endif
            </div>
            @foreach ($shop->footerColumns() as $column)
                <div class="fcol">
                    <h4>{{ $column['title'] }}</h4>
                    @foreach ($column['links'] as $link)
                        <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                    @endforeach
                </div>
            @endforeach
            @if ($shop->footer_script)
                <div class="ff-script">
                    <p>{!! nl2br(e(str_replace(', ', ",\n", $shop->text('footer_script')))) !!}</p>
                    <x-icon name="heart" :size="22" :stroke="1.6" />
                    <img src="{{ asset('img/fresh/leaf-f.jpg') }}" alt="" loading="lazy">
                </div>
            @endif
        </div>
        <div class="ff-bottom">
            <span>© {{ date('Y') }} {{ $shop->shop_name }}. Tous droits réservés.</span>
            <div class="pay pay-methods">
                @foreach ($shop->paymentMethods() as $value => $label)
                    <span class="pm pm-{{ $value }}"><x-payment-icon :method="$value" :size="26" /><em>{{ $label }}</em></span>
                @endforeach
            </div>
            <div class="lang"><x-icon name="globe" :size="20" :stroke="1.5" /> {{ $shop->language_label }} <i>|</i> {{ $shop->currency_code }}</div>
        </div>
        @if ($shop->footer_credits)
            <div class="photo-credit">{!! \Illuminate\Support\Str::inlineMarkdown($shop->text('footer_credits'), ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
        @endif
    </div>
</footer>
