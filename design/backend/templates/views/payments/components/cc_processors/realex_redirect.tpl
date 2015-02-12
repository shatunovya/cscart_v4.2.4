<div class="control-group">
    <label class="control-label" for="merchant_id">{__("merchant_id")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][merchant_id]" id="merchant_id" value="{$processor_params.merchant_id}" >
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="account">{__("payments.realex.subaccount")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][account]" id="account" value="{$processor_params.account}" >
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="secret_word">{__("shared_secret")}:</label>
    <div class="controls">
        <input type="password" name="payment_data[processor_params][secret_word]" id="secret_word" value="{$processor_params.secret_word}" >
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="currency">{__("currency")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][currency]" id="currency">
            
            <option value="EUR" {if $processor_params.currency == "EUR"}selected="selected"{/if}>{__("currency_code_eur")}</option>
            <option value="GBP" {if $processor_params.currency == "GBP"}selected="selected"{/if}>{__("currency_code_gbp")}</option>
            <option value="USD" {if $processor_params.currency == "USD"}selected="selected"{/if}>{__("currency_code_usd")}</option>
            
        </select>
    </div>
</div>


<div class="control-group">
    <label class="control-label" for="mode">{__("test_live_mode")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][mode]" id="mode">
            <option value="test" {if $processor_params.mode == "test"}selected="selected"{/if}>{__("test")}</option>
            <option value="live" {if $processor_params.mode == "live"}selected="selected"{/if}>{__("live")}</option>
        </select>
    </div>
</div>


<div class="control-group">
    <label class="control-label" for="settlement">{__("payments.realex.settlement")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][settlement]" id="settlement">
            <option value="auto" {if $processor_params.settlement == "auto"}selected="selected"{/if}>{__("payments.realex.auto_settled")}</option>
            <option value="delayed" {if $processor_params.settlement == "delayed"}selected="selected"{/if}>{__("payments.realex.delayed_settlement")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label">{__("payments.realex.referring_url")}:</label>
    <div class="controls">
        <b>{fn_url("", "C")}</b>
    </div>
</div>

{include file="common/subheader.tpl" title=__("payments.realex.text_status_map") target="#text_realex_status_map"}

<div id="text_realex_status_map" class="in collapse">
    {assign var="statuses" value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses}
    
    <div class="control-group">
        <label class="control-label" for="elm_realex_successful">{__("successful")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][statuses][successful]" id="elm_realex_successful">
                {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if (isset($processor_params.statuses.successful) && $processor_params.statuses.successful == $k) || (!isset($processor_params.statuses.successful) && $k == 'P')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>
    
    <div class="control-group">
        <label class="control-label" for="elm_realex_declined">{__("declined")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][statuses][declined]" id="elm_realex_declined">
                {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if (isset($processor_params.statuses.declined) && $processor_params.statuses.declined == $k) || (!isset($processor_params.statuses.declined) && $k == 'D')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>
    
    <div class="control-group">
        <label class="control-label" for="elm_realex_card_lost_or_stolen">{__("payments.realex.card_lost_or_stolen")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][statuses][card_lost_or_stolen]" id="elm_realex_card_lost_or_stolen">
                {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if (isset($processor_params.statuses.card_lost_or_stolen) && $processor_params.statuses.card_lost_or_stolen == $k) || (!isset($processor_params.statuses.card_lost_or_stolen) && $k == 'D')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="elm_realex_refferal">{__("payments.realex.refferal")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][statuses][refferal]" id="elm_realex_refferal">
                {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if (isset($processor_params.statuses.refferal) && $processor_params.statuses.refferal == $k) || (!isset($processor_params.statuses.refferal) && $k == 'D')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>
    
    <div class="control-group">
        <label class="control-label" for="elm_realex_bank_error">{__("payments.realex.bank_error")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][statuses][bank_error]" id="elm_realex_bank_error">
                {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if (isset($processor_params.statuses.bank_error) && $processor_params.statuses.bank_error == $k) || (!isset($processor_params.statuses.bank_error) && $k == 'F')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>
    
    <div class="control-group">
        <label class="control-label" for="elm_realex_realex_error">{__("payments.realex.realex_error")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][statuses][realex_error]" id="elm_realex_realex_error">
                {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if (isset($processor_params.statuses.realex_error) && $processor_params.statuses.realex_error == $k) || (!isset($processor_params.statuses.realex_error) && $k == 'F')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>
    
    <div class="control-group">
        <label class="control-label" for="elm_realex_incorrect_request">{__("payments.realex.incorrect_request")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][statuses][incorrect_request]" id="elm_realex_incorrect_request">
                {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if (isset($processor_params.statuses.incorrect_request) && $processor_params.statuses.incorrect_request == $k) || (!isset($processor_params.statuses.incorrect_request) && $k == 'F')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>
    
    <div class="control-group">
        <label class="control-label" for="elm_realex_connector_error">{__("payments.realex.connector_error")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][statuses][connector_error]" id="elm_realex_connector_error">
                {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if (isset($processor_params.statuses.connector_error) && $processor_params.statuses.connector_error == $k) || (!isset($processor_params.statuses.connector_error) && $k == 'F')}selected="selected"{/if}>{$s}</option>
                {/foreach}
            </select>
        </div>
    </div>
</div>