@extends('user.layouts.master')

@section('breadcrumb')
    @include('user.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('user.dashboard'),
            ],
        ],
        'active' => __('menu'),
    ])
@endsection

@section("link-css")
<link rel="stylesheet" href="{{asset("frontend/css/menu.css")}}">
@endsection

@section('content')
<div class="body-wrapper">
    <!--  -->
    <div class="menu-content">
        <div class="head-menu">
            <!--  -->
            <div class="menu-info">
                <div class="icone">
                    <i class="fa fa-cutlery" aria-hidden="true"></i>
                </div>
                <div class="menu-info-texte">
                    <p class="titre">Petit dejeune</p>
                    <div class="dispo">
                        <p>disponible</p>
                    </div>
                </div>
            </div>
            <!--  -->
            <!--  -->
            <div class="temps">
                <p class="date">
                    <span>Mer. 18 Avr. 2023</span> - <span>18H30</span>
                </p>
            </div>
            <!--  -->
            <!--  -->
            <div class="display" data-active="1">
                <button>
                    <i class="fa fa-chevron-down" aria-hidden="true"></i>
                </button>
            </div>
            <!--  -->
        </div>
        <!--  -->
        <div class="body-menu" data-active="1">
            <div class="plat-content">
                <div class="plat-image">
                    <img src="https://www.la-gannerie.com/ressources/images/d0aae63c434f.jpg" alt="plat">
                </div>
                <div class="plat-textes">
                    <p class="plat-titre">Lorem, ipsum dolor.</p>
                    <p class="plat-desc">Lorem ipsum dolor sit, amet consectetur adipisicing.</p>
                </div>
            </div>
            <!--  -->
            <div class="plat-content">
                <div class="plat-image">
                    <img src="https://www.la-gannerie.com/ressources/images/d0aae63c434f.jpg" alt="plat">
                </div>
                <div class="plat-textes">
                    <p class="plat-titre">Lorem, ipsum dolor.</p>
                    <p class="plat-desc">Lorem ipsum dolor sit, amet consectetur adipisicing.</p>
                </div>
            </div>
            <!--  -->
            <div class="plat-content">
                <div class="plat-image">
                    <img src="https://www.la-gannerie.com/ressources/images/d0aae63c434f.jpg" alt="plat">
                </div>
                <div class="plat-textes">
                    <p class="plat-titre">Lorem, ipsum dolor.</p>
                    <p class="plat-desc">Lorem ipsum dolor sit, amet consectetur adipisicing.</p>
                </div>
            </div>
        </div>
        <!--  -->
        <div class="footer-menu">
            <p class="nom-restaurant">Nom du restaurant</p>
            <button>
                <span>j'aime</span>
                <i class="fa fa-thumbs-up" aria-hidden="true"></i>
              </button>
        </div>
    </div>
    <!--  -->
</div>
@endsection

@push('script')
<script defer>
    // javascript code
    let btnsDisplay = document.querySelectorAll(".display");
let bodyMenus = document.querySelectorAll(".body-menu");

btnsDisplay.forEach(btn => {
    btn.addEventListener("click", () =>{
        btn.classList.toggle("active");
        for(let i = 0; i < bodyMenus.length; i++){
            if(btn.getAttribute("data-active") == bodyMenus[i].getAttribute("data-active")){
                bodyMenus[i].classList.toggle("active");
            }else{
                bodyMenus[i].classList.remove("active");
            }
        }
    })
})
</script>
@endpush