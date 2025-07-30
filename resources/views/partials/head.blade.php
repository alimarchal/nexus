<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/icon-images/favicon-32x32.png" sizes="any">
<link rel="icon" href="/icon-images/favicon-32x32.png" type="image/svg+xml">
<link rel="icon" href="/icon-images/favicon-32x32.png" type="image/png">
<link rel="apple-touch-icon" href="/icon-images/favicon-32x32.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
<script src="{{ url('apexcharts/apexcharts.js') }}"></script>
<link href="{{ url('select2/select2.min.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="{{ url('jsandcss/daterangepicker.min.css') }}">
<script src="{{ url('jsandcss/moment.min.js') }}"></script>
<script src="{{ url('jsandcss/knockout-3.5.1.js') }}" defer></script>
<script src="{{ url('jsandcss/daterangepicker.min.js') }}" defer></script>
<style>
            .select2 {
                /*width:100%!important;*/
                width: auto !important;
                display: block;
            }

            .select2-container .select2-selection--single {
                height: auto; /* Reset the height if necessary */
                padding: 0.7rem 1rem; /* This should match Tailwind's py-2 px-4 */
                line-height: 1.25; /* Adjust based on Tailwind's line height for consistency */
                /*font-size: 0.875rem; !* Matches Tailwind's text-sm *!*/
                border: 1px solid #d1d5db; /* Tailwind's border-gray-300 */
                border-radius: 0.375rem; /* Tailwind's rounded-md */
                box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* Tailwind's shadow-sm */
            }

            .select2-container .select2-selection--single .select2-selection__rendered {
                line-height: 1.25; /* Aligns text vertically */
                padding-left: 0; /* Adjust if needed */
                padding-right: 0; /* Adjust if needed */
            }

            /*.select2-selection__arrow*/
            .select2-container .select2-selection--single {
                height: auto; /* Ensure the arrow aligns with the adjusted height */
                right: 0.5rem; /* Align the arrow similarly to Tailwind's padding */

            }

            .select2-selection__arrow {
                top: 8px!important;
                right: 10px!important;
            }
</style>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
