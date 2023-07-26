 <!-- jquery -->
 <script src="{{ asset('public/frontend/') }}/js/jquery-3.5.1.min.js"></script>
 <!-- bootstrap js -->
 <script src="{{ asset('public/frontend/') }}/js/bootstrap.bundle.min.js"></script>
 <!-- swipper js -->
 <script src="{{ asset('public/frontend/') }}/js/swiper.min.js"></script>
 <!-- apexcharts js -->
 <script src="{{ asset('public/frontend/') }}/js/apexcharts.min.js"></script>

 <script src="{{ asset('public/backend/js/select2.min.js') }}"></script>
 <script src="{{ asset('public/backend/library/popup/jquery.magnific-popup.js') }}"></script>
  <!-- nice-select js -->
  <script src="{{ asset('public/frontend/') }}/js/jquery.nice-select.js"></script>
 <!-- smooth scroll js -->
 <script src="{{ asset('public/frontend/') }}/js/smoothscroll.min.js"></script>
 <!-- main -->
 <script src="{{ asset('public/frontend/') }}/js/main.js"></script>
 <script>
    function laravelCsrf() {
    return $("head meta[name=csrf-token]").attr("content");
  }
//for popup
function openAlertModal(URL,target,message,actionBtnText = "Remove",method = "DELETE"){
    if(URL == "" || target == "") {
        return false;
    }

    if(message == "") {
        message = "Are you sure to delete ?";
    }
    var method = `<input type="hidden" name="_method" value="${method}">`;
    openModalByContent(
        {
            content: `<div class="card modal-alert border-0">
                        <div class="card-body">
                            <form method="POST" action="${URL}">
                                <input type="hidden" name="_token" value="${laravelCsrf()}">
                                ${method}
                                <div class="head mb-3">
                                    ${message}
                                    <input type="hidden" name="target" value="${target}">
                                </div>
                                <div class="foot d-flex align-items-center justify-content-between">
                                    <button type="button" class="modal-close btn btn--info rounded text-light">Close</button>
                                    <button type="submit" class="alert-submit-btn btn btn--danger btn-loading rounded text-light">${actionBtnText}</button>
                                </div>
                            </form>
                        </div>
                    </div>`,
        },

    );
  }
function openModalByContent(data = {
content:"",
animation: "mfp-move-horizontal",
size: "medium",
}) {
$.magnificPopup.open({
    removalDelay: 500,
    items: {
    src: `<div class="white-popup mfp-with-anim ${data.size ?? "medium"}">${data.content}</div>`, // can be a HTML string, jQuery object, or CSS selector
    },
    callbacks: {
    beforeOpen: function() {
        this.st.mainClass = data.animation ?? "mfp-move-horizontal";
    },
    open: function() {
        var modalCloseBtn = this.contentContainer.find(".modal-close");
        $(modalCloseBtn).click(function() {
        $.magnificPopup.close();
        });
    },
    },
    midClick: true,
});
}

</script>
 
 @include('admin.partials.notify')
