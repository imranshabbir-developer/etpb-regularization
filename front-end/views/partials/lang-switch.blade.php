@php($current = app()->getLocale())
<nav class="lang-switch {{ $class ?? '' }}" aria-label="{{ __('Language') }}">
    <a href="{{ route('locale.switch', 'ur') }}" lang="ur" dir="rtl" hreflang="ur"
       class="lang-switch-opt lang-switch-ur {{ $current === 'ur' ? 'is-active' : '' }}"
       @if ($current === 'ur') aria-current="true" @endif>اردو</a>
    <a href="{{ route('locale.switch', 'en') }}" lang="en" dir="ltr" hreflang="en"
       class="lang-switch-opt {{ $current === 'en' ? 'is-active' : '' }}"
       @if ($current === 'en') aria-current="true" @endif>English</a>
</nav>
