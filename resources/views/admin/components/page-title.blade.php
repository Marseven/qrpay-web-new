<div class="left">
    <div class="icon">
        <button class="sidebar-menu-bar">
            <i class="fas fa-exchange-alt"></i>
        </button>
    </div>
    <div class="content">
        <h3 class="title">{{ $title }}</h3>
        <p>{{ isset($sub_title) ? $sub_title : "Bienvenu dans l'espace d'administration de " . $basic_settings->site_name . ' ' }}
        </p>
    </div>
</div>
