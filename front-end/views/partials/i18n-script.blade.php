@php
    $etpbI18n = [
        'File ready to upload' => __('File ready to upload'),
        'Submitted'            => __('Submitted'),
        'Regularized'          => __('Regularized'),
        'Total'                => __('Total'),
        'Value'                => __('Value'),
        'Rs.'                  => __('Rs.'),
        'cr'                   => __('cr'),
        'lac'                  => __('lac'),
        'months'               => app()->getLocale() === 'ur'
            ? ['جنوری', 'فروری', 'مارچ', 'اپریل', 'مئی', 'جون', 'جولائی', 'اگست', 'ستمبر', 'اکتوبر', 'نومبر', 'دسمبر']
            : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    ];
@endphp
<script>
    window.ETPB_I18N = @json($etpbI18n);
</script>
