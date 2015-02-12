{if "discussion.add"|fn_check_view_permissions && !("MULTIVENDOR"|fn_allowed_for && $runtime.company_id && ($runtime.company_id != $object_company_id || $discussion.object_type == 'M'))}
    {capture name="add_new_picker"}
        <div class="tabs cm-j-tabs">
            <ul class="nav nav-tabs">
                <li id="tab_add_post" class="cm-js active"><a>{__("general")}</a></li>
            </ul>
        </div>
        <form id='form' action="{""|fn_url}" method="post" class="form-horizontal form-edit  cm-disable-empty-files " enctype="multipart/form-data">

            <div class="cm-tabs-content cm-no-hide-input" id="content_tab_add_post">
                <input type ="hidden" name="post_data[thread_id]" value="{$discussion.thread_id}" />
                <input type ="hidden" name="redirect_url" value="{$config.current_url}&amp;selected_section=discussion" />

                <div class="control-group">
                    <label for="post_data_name" class="cm-required control-label">{__("name")}:</label>
                    <div class="controls">
                        <input type="text" name="post_data[name]" id="post_data_name" value="{if $auth.user_id}{$user_info.firstname} {$user_info.lastname}{/if}" disabled="disabled">
                    </div>
                </div>

                {if "discussion.update"|fn_check_view_permissions}
                    {if $discussion.type == "R" || $discussion.type == "B"}
                        <div class="control-group">
                            <label for="rating_value" class="control-label cm-required cm-multiple-radios">{__("your_rating")}</label>
                            <div class="controls clearfix">
                                {include file="addons/discussion/views/discussion_manager/components/rate.tpl" rate_id="rating_value" rate_name="post_data[rating_value]" disabled=true}
                            </div>
                        </div>
                    {/if}
                {/if}

                {hook name="discussion:add_post"}
                {if $discussion.type == "C" || $discussion.type == "B"}
                    <div class="control-group">
                        <label for="message" class="control-label">{__("your_message")}:</label>
                        <div class="controls">
                            <textarea name="post_data[message]" id="message" class="input-textarea-long" cols="70" rows="8" disabled="disabled"></textarea>
                        </div>
                    </div>
                {/if}
                {/hook}
            </div>

            <div class="buttons-container">
                {include file="buttons/save_cancel.tpl" but_text=__("add") but_name="dispatch[discussion.add]" cancel_action="close" hide_first_button=false}
            </div>

        </form>
    {/capture}

    {include file="common/popupbox.tpl" id="add_new_post" text=__("new_post") content=$smarty.capture.add_new_picker act="fake"}
{/if}