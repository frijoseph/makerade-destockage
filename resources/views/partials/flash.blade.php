@php
    $flash = ['status' => session('status')];

    if ($welcome = session('welcome_code')) {
        $flash['modal'] = [
            'icon' => 'success',
            'title' => 'Bienvenue, voici votre cadeau 🎁',
            'html' => '<p>Votre code de bienvenue :</p><div class="mk-code" data-copy="'.e($welcome['code']).'"><b>'.e($welcome['code']).'</b></div><p class="mk-code-note"><strong>'.e($welcome['benefit']).'</strong> sur votre prochaine commande. Il s’applique dans le panier.</p>',
            'button' => 'Merci !',
            'confetti' => true,
        ];
        $flash['status'] = null;
    }

    if (($showErrors ?? false) && $errors->any()) {
        $flash['errors'] = $errors->all();
    }
@endphp
<script>window.MK_FLASH = {!! json_encode($flash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!};</script>
