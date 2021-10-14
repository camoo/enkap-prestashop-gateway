<div class="panel card mt-2" id="e_nkap_panel">
    <div class="panel-heading card-header">
        <h3>{l s='SmobilPay payment status' d='Modules.E_nkap.Shop'}</h3>
    </div>
    <div class="card-body">
        <p>
            <span>{l s='Merchant ID' d='Modules.E_nkap.Shop'}: <strong>{$en_payment.merchant_reference_id}</strong></span><br>
            <span>{l s='Payment transaction ID' d='Modules.E_nkap.Shop'}: <strong>{$en_payment.order_transaction_id}</strong></span><br>
            <span>{l s='Payment status' d='Modules.E_nkap.Shop'}: <strong>{if empty($en_payment.status)}{l s='Pending' d='Modules.E_nkap.Shop'}{else}{$en_payment.status}{/if}</strong></span>
        </p>
        {if {$en_payment.status}|in_array:['PENDING', 'IN_PROGRESS'] || empty($en_payment.status)}
            <p>
                <a class="btn btn-primary" href="{$link}">{l s='Check Payment status' d='Modules.E_nkap.Shop'}</a>
            </p>
        {/if}

    </div>
</div>
