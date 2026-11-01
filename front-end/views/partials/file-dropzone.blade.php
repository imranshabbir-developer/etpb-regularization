{{--
    Styled upload control. Usage:
      @include('partials.file-dropzone', [
          'name' => 'file',
          'id' => 'file',
          'accept' => '.pdf,.jpg,.jpeg,.png',
          'required' => true,
          'hint' => 'PDF or clear photo. Up to 10 MB.',
          'title' => 'Drop a file here or click to browse',
          'subtitle' => 'PDF, JPEG or PNG',
      ])
--}}
@php
    $dzId = $id ?? $name ?? 'file';
    $dzName = $name ?? 'file';
    $dzAccept = $accept ?? '.pdf,.jpg,.jpeg,.png,.tif,.tiff,.webp';
    $dzRequired = ! empty($required);
    $dzHint = $hint ?? null;
    $dzTitle = $title ?? 'Drop a file here or click to browse';
    $dzSubtitle = $subtitle ?? 'PDF or a clear photo';
    $dzInvalid = $errors->has($dzName) ?? false;
@endphp

<div class="file-drop {{ $dzInvalid ? 'is-invalid' : '' }}" data-file-drop>
    <input type="file"
           id="{{ $dzId }}"
           name="{{ $dzName }}"
           class="file-drop-input"
           accept="{{ $dzAccept }}"
           @if ($dzRequired) required @endif
           data-file-drop-input>

    <label for="{{ $dzId }}" class="file-drop-surface">
        <span class="file-drop-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" width="28" height="28">
                <path d="M12 16V7m0 0l-3.5 3.5M12 7l3.5 3.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M5 16.5V18a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-1.5" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="file-drop-copy">
            <strong class="file-drop-title" data-file-drop-title>{{ $dzTitle }}</strong>
            <span class="file-drop-sub" data-file-drop-sub>{{ $dzSubtitle }}</span>
            <span class="file-drop-name" data-file-drop-name hidden></span>
        </span>
        <span class="file-drop-cta">Choose file</span>
    </label>
</div>
@if ($dzHint)
    <p class="hint mt-1">{{ $dzHint }}</p>
@endif
@error($dzName)<p class="error-text">{{ $message }}</p>@enderror
