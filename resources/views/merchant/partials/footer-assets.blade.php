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
 <script>
    var chart1 = $('#chart1');
    var chart_one_data = chart1.data('chart_one_data');
    var month_day = chart1.data('month_day');
    var options = {
        series: [
            {
            name: 'Pending',
            color: "#0C56DB",
            data: chart_one_data.pending_data
            }, {
            name: 'Completed',
            color: "rgba(0, 227, 150, 0.85)",
            data: chart_one_data.success_data
            }, {
            name: 'Canceled',
            color: "#dc3545",
            data: chart_one_data.canceled_data
            }, {
            name: 'Hold',
            color: "#ded7e9",
            data: chart_one_data.hold_data
            }
        ],
        chart: {
            height: 350,
            type: "area",
            toolbar: {
                show: false,
            },
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            curve: "smooth",
        },
        xaxis: {
            type: "datetime",
            categories:month_day,
        },
        tooltip: {
            x: {
                format: "dd/MM/yy HH:mm",
            },
        },
    };

    var chart = new ApexCharts(document.querySelector("#chart1"), options);
    chart.render();

    // var options = {
    //     series: [
    //         {
    //             data: [44, 55, 41, 64, 22, 43, 21],
    //             color: "#0C56DB",
    //         },
    //         {
    //             data: [53, 32, 33, 52, 13, 44, 32],
    //         },
    //     ],
    //     chart: {
    //         type: "bar",
    //         toolbar: {
    //             show: false,
    //         },
    //         height: 350,
    //     },
    //     plotOptions: {
    //         bar: {
    //             horizontal: true,
    //             dataLabels: {
    //                 position: "top",
    //             },
    //         },
    //     },
    //     dataLabels: {
    //         enabled: true,
    //         offsetX: -6,
    //         style: {
    //             fontSize: "12px",
    //             colors: ["#fff"],
    //         },
    //     },
    //     stroke: {
    //         show: true,
    //         width: 1,
    //         colors: ["#ded7e9"],
    //     },
    //     tooltip: {
    //         shared: true,
    //         intersect: false,
    //     },
    //     xaxis: {
    //         categories: [2001, 2002, 2003, 2004, 2005, 2006, 2007],
    //     },
    // };

    // var chart = new ApexCharts(document.querySelector("#chart2"), options);
    // chart.render();
</script>
 @include('admin.partials.notify')
