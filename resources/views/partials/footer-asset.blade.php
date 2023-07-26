
<!-- jquery -->
<script src="{{ asset('public/frontend/') }}/js/jquery-3.5.1.min.js"></script>
<!-- bootstrap js -->
<script src="{{ asset('public/frontend/') }}/js/bootstrap.bundle.min.js"></script>
<!-- swipper js -->
<script src="{{ asset('public/frontend/') }}/js/swiper.min.js"></script>
<!-- wow js file -->
{{-- <script src="{{ asset('public/frontend/') }}/js/wow.min.js"></script> --}}
<!-- smooth scroll js -->
<script src="{{ asset('public/frontend/') }}/js/smoothscroll.min.js"></script>
<!-- main -->
<!-- nice select js -->
<script src="{{ asset('public/frontend/js/jquery.nice-select.js') }}"></script>
<script src="{{ asset('public/backend/js/select2.min.js') }}"></script>

<script src="{{ asset('public/frontend/') }}/js/odometer.min.js"></script>
<!-- viewport js -->
<script src="{{ asset('public/frontend/') }}/js/viewport.jquery.js"></script>

<script src="{{ asset('public/frontend/') }}/js/main.js"></script>
{{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script> --}}

<script>
     $(".langSel").on("change", function() {
        window.location.href = "{{route('index')}}/change/"+$(this).val() ;
    });
</script>


@include('admin.partials.notify')
