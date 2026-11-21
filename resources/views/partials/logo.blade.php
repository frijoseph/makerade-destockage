<a href="{{ route('home') }}" class="logo {{ !empty($footer) ? 'logo-foot' : '' }}" aria-label="{{ $shop->shop_name }} — accueil">
    <img src="{{ $shop->image('logo') }}" alt="{{ $shop->shop_name }}" width="120" height="104">
</a>
