<section class="newsband">
    <img class="nb-leaf l" src="{{ asset('img/fresh/leaf-l.jpg') }}" alt="" loading="lazy">
    <img class="nb-leaf r" src="{{ asset('img/fresh/leaf-r.jpg') }}" alt="" loading="lazy">
    <div class="container newsband-in">
        <div class="nb-txt"><h3>Restez propre. Restez informé.</h3><p>Abonnez-vous pour recevoir nos dernières offres, les arrivages et des promotions exclusives.</p></div>
        <form class="news-form" id="newsForm" novalidate>
            <input type="email" id="newsEmail" placeholder="Entrez votre adresse e-mail" aria-label="Adresse e-mail" required>
            <button class="nb-btn">S'abonner <x-icon name="arrow-right" :size="15" :stroke="2.2" /></button>
        </form>
    </div>
</section>

<footer class="fresh-foot">
    <div class="container">
        <div class="ff-cols">
            <div class="ff-brand">
                @include('partials.logo', ['footer' => true])
                <p>Produits d'entretien et de bien-être pour une maison saine et une vie sereine.</p>
                <div class="social">
                    <a href="#" aria-label="Facebook"><x-icon name="facebook" :size="20" /></a>
                    <a href="#" aria-label="Instagram"><x-icon name="instagram" :size="20" /></a>
                    <a href="#" aria-label="YouTube"><x-icon name="youtube" :size="20" /></a>
                    <a href="#" aria-label="Pinterest"><x-icon name="pinterest" :size="20" /></a>
                    <a href="#" aria-label="TikTok"><x-icon name="tiktok" :size="20" /></a>
                </div>
            </div>
            <div class="fcol">
                <h4>Boutique</h4>
                <a href="{{ route('shop', ['cat' => 'entretien']) }}">Entretien ménager</a>
                <a href="{{ route('shop', ['cat' => 'lessive']) }}">Lessives &amp; linge</a>
                <a href="{{ route('shop', ['cat' => 'cuisine']) }}">Cuisine &amp; vaisselle</a>
                <a href="{{ route('shop', ['cat' => 'bain']) }}">Salle de bain</a>
                <a href="{{ route('shop', ['cat' => 'hygiene']) }}">Hygiène &amp; désinfection</a>
                <a href="{{ route('shop', ['cat' => 'bienetre']) }}">Soins &amp; bien-être</a>
                <a href="{{ route('deals') }}">Offres spéciales</a>
            </div>
            <div class="fcol">
                <h4>Service client</h4>
                <a href="{{ route('track') }}">Suivre ma commande</a>
                <a href="{{ route('info', 'returns') }}">Retours &amp; remboursements</a>
                <a href="{{ route('info', 'shipping') }}">Livraison</a>
                <a href="{{ route('info', 'terms') }}">Conditions générales</a>
                <a href="{{ route('contact') }}">Contact</a>
            </div>
            <div class="fcol">
                <h4>Entreprise</h4>
                <a href="{{ route('info', 'about') }}">À propos</a>
                <a href="{{ route('brands') }}">Marques</a>
                <a href="{{ route('blogs') }}">Blog</a>
                <a href="{{ route('info', 'privacy') }}">Confidentialité</a>
                <a href="{{ route('contact') }}">Devenir partenaire</a>
            </div>
            <div class="ff-script">
                <p>Maison propre,<br>vie<br>sereine</p>
                <x-icon name="heart" :size="22" :stroke="1.6" />
                <img src="{{ asset('img/fresh/leaf-f.jpg') }}" alt="" loading="lazy">
            </div>
        </div>
        <div class="ff-bottom">
            <span>© {{ date('Y') }} Makerade Destockage. Tous droits réservés.</span>
            <div class="pay">
                <span class="p-visa">VISA</span>
                <span class="p-mc"><i></i><i></i></span>
                <span class="p-pp">P</span>
                <span class="p-ap"><svg width="12" height="14" viewBox="0 0 12 14"><path d="M8.500 0c.1 1-.4 2-1 2.600-.6.700-1.500 1.100-2.300 1 0-1 .5-1.900 1.100-2.500C6.900.4 7.800 0 8.500 0zM10.700 10.400c-.5 1.100-1 2.100-1.800 3-.7.700-1.300.7-2 .4-.7-.3-1.300-.3-2 0-.9.400-1.400.3-2-.3C1.400 11.400.500 8 2 5.700c.7-1.100 1.800-1.700 2.900-1.700.7 0 1.300.4 2 .4.700 0 1.100-.4 2.100-.4 1 0 1.900.5 2.500 1.300-2.200 1.200-1.800 4.400.2 5.100z" fill="#111"/></svg>Pay</span>
                <span class="p-gp"><b>G</b> Pay</span>
            </div>
            <div class="lang"><x-icon name="globe" :size="20" :stroke="1.5" /> Français <i>|</i> USD <x-icon name="chev-down" :size="13" /></div>
        </div>
        <p class="photo-credit">Photos d'ambiance : <a href="https://stocksnap.io" target="_blank" rel="noopener">StockSnap</a> et <a href="https://www.rawpixel.com" target="_blank" rel="noopener">Rawpixel</a> (CC0). Photos de produits : <a href="https://world.openbeautyfacts.org" target="_blank" rel="noopener">Open Beauty Facts</a> (CC BY-SA 3.0). Marques citées à titre d'illustration.</p>
    </div>
</footer>
