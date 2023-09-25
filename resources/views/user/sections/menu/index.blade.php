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
<!-- Menu du jour - css -->
<link rel="stylesheet" href="{{ asset('public/frontend/') }}/css/menu.css">

@section('content')
<div class="body-wrapper">
    @if (count($menus) <= 0)
    <div class="empty-menus">
            <p class="empty-menu-inc">
                <i class="fa fa-calendar" aria-hidden="true"></i>
            </p>
            <p class="empty-menu-txt">
                Rien de prévus pour le moment.
            </p>
    </div>
    @endif
    @for ($i = 0; $i < count($menus); $i++)
         <!--  -->
    <div class="menu-content">
        <div class="head-menu">
            <!--  -->
            <div class="menu-info">
                <div class="icone">
                    <i class="fa fa-shopping-basket" aria-hidden="true"></i>
                </div>
                <div class="menu-info-texte">
                    <p class="titre">{{$menus[$i]["titre"]}},{{$menus[$i]["id"]}}</p>
                    @if ($menus[$i]["disponible"])
                    <div class="dispo true">
                        <p>disponible</p>
                    </div>
                    @else
                    <div class="dispo fals">
                        <p>Non disponible</p>
                    </div>
                    @endif
                </div>
            </div>
            <!--  -->
            <!--  -->
            <div class="temps">
                <p class="date"><span>{{$menus[$i]["debut"]}}</span> a <span>{{$menus[$i]["fin"]}}</span></p>
            </div>
            <!--  -->
            <!--  -->
            <div class="display {{$i == 0 ? "active" : ""}}" data-active="{{$i}}">
                <button>
                    <i class="fa fa-chevron-down" aria-hidden="true"></i>
                </button>
            </div>
            <!--  -->
        </div>
        <!--  -->
        <div class="body-menu {{count($menus[$i]->plats) == 0 ? "empty-body" : "not-empty-body" }} {{$i == 0 ? "active" : ""}}" data-active="{{$i}}">
            @if (count($menus[$i]->plats) <= 0)
                <div class="empty-plats">
                    <p class="empty-plats-icn">
                        <i class="fa fa-coffee" aria-hidden="true"></i>
                    </p>
                    <p class="empty-plats-txt">
                        Pas de plats pour le moment.
                    </p>
                </div>
            @endif
            <!-- -->
            @for ($b = 0; $b < count($menus[$i]->plats); $b++)
            @if ($menus[$i]->plats[$b]["menu_id"] == $menus[$i]["id"])
            <div class="plat-content">
                <div class="plat-image">
                    <img src="https://www.la-gannerie.com/ressources/images/d0aae63c434f.jpg" alt="plat">
                </div>
                <p class="plat-titre">{{$menus[$i]->plats[$b]["titre"]}}</p>
            </div>
            @endif
            @endfor
            <!-- -->
        </div>
        <!--  -->
        <div class="footer-menu">
            <p class="nom-restaurant">{{$menus[$i]->menus[$i]["restaurant"]}}</p>
            <button>
                <span>j'aime</span>
                <i class="fa fa-thumbs-up" aria-hidden="true"></i>
              </button>
        </div>
    </div>
    <!--  -->
    @endfor
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