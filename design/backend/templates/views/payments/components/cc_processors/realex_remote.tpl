<div class="control-group">
    <label class="control-label" for="merchant_id">{__("merchant_id")}:</label>

    <div class="controls">
        <input type="text" name="payment_data[processor_params][merchant_id]" id="merchant_id"
               value="{$processor_params.merchant_id}">
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="account">{__("payments.realex.subaccount")}:</label>

    <div class="controls">
        <input type="text" name="payment_data[processor_params][account]" id="account"
               value="{$processor_params.account}">
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="secret_word">{__("shared_secret")}:</label>

    <div class="controls">
        <input type="password" name="payment_data[processor_params][secret_word]" id="secret_word"
               value="{$processor_params.secret_word}">
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="currency">{__("currency")}:</label>

    <div class="controls">
        <select name="payment_data[processor_params][currency]" id="currency">
            <option value="EUR"
                    {if $processor_params.currency == "EUR"}selected="selected"{/if}>{__("currency_code_eur")}</option>
            <option value="GBP"
                    {if $processor_params.currency == "GBP"}selected="selected"{/if}>{__("currency_code_gbp")}</option>
            <option value="USD"
                    {if $processor_params.currency == "USD"}selected="selected"{/if}>{__("currency_code_usd")}</option>
        </select>
    </div>
</div>


<div class="control-group">
    <label class="control-label" for="3d_secure">{__("3d_secure")}:</label>

    <div class="controls">
        <select name="payment_data[processor_params][3d_secure]" id="3d_secure">
            <option value="enabled"
                    {if $processor_params.3d_secure == "enabled"}selected="selected"{/if}>{__("enabled")}</option>
            <option value="disabled"
                    {if $processor_params.3d_secure == "disabled"}selected="selected"{/if}>{__("disabled")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="liability_shift_required">{__("payments.realex.liability_shift_required")}:</label>

    <div class="controls">
        <select name="payment_data[processor_params][liability_shift_required]" id="liability_shift_required">
            <option value="yes"
                    {if $processor_params.liability_shift_required == "yes"}selected="selected"{/if}>{__("yes")}</option>
            <option value="no"
                    {if $processor_params.liability_shift_required == "no"}selected="selected"{/if}>{__("no")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="settlement">{__("payments.realex.settlement")}:</label>

    <div class="controls">
        <select name="payment_data[processor_params][settlement]" id="settlement">
            <option value="auto"
                    {if $processor_params.settlement == "auto"}selected="selected"{/if}>{__("payments.realex.auto_settled")}</option>
            <option value="delayed"
                    {if $processor_params.settlement == "delayed"}selected="selected"{/if}>{__("payments.realex.delayed_settlement")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label">{__("payments.realex.referring_ip")}:</label>

    <div class="controls">
        <b>{$smarty.server.SERVER_ADDR}</b>
    </div>
</div>