{if $runtime.twigmo.admin_connection.access_id}

    <div class="twg-mobile-app-link">
        <a href="{"twigmo_admin_app.view"|fn_url}">{__("twgadmin_mobile_app")}</a>
    </div>

    <script type="text/javascript">
        (function(_, $) {
            $(document).ready(function() {
                $('div.twg-mobile-app-link').detach().insertBefore('ul.nav.hover-show.nav-pills').show();
            });
        }(Tygh, Tygh.$));
    </script>

{/if}