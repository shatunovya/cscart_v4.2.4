{capture name="mainbox"}
    <form class="form-edit form-horizontal">
        <div id="content_twigmo">
            {include file="addons/twigmo/settings/admin_app.tpl" hide_header="true"}
        </div>
    </form>
{/capture}

{include file="common/mainbox.tpl" title=__("twgadmin_mobile_admin_application") content=$smarty.capture.mainbox}
