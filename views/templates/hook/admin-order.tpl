<div class="panel card mt-2" id="e_nkap_panel">
    <div class="panel-heading card-header">
        <h3>{l s='E-Nkap payment status' d='Modules.E_nkap.Admin'}</h3>
    </div>
    <div class="card-body">
        <p>
            <span>{l s='Merchand ID' d='Modules.E_nkap.Admin'}: <strong>{$en_payment.merchant_reference_id}</strong></span><br>
            <span>{l s='Payment transaction ID' d='Modules.E_nkap.Admin'}: <strong>{$en_payment.order_transaction_id}</strong></span><br>
            <span>{l s='Payment status' d='Modules.E_nkap.Admin'}: <strong>{$en_payment.status}</strong></span>
        </p>
        <p>
            <a class="btn btn-primary" target="_blank" href="{$link}">{l s='Check Payment status' d='Modules.E_nkap.Admin'}</a>
        </p>
    </div>
</div>
