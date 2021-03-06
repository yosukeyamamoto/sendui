<h2 class="mainpage account float-left">{$session->login}, votre compte</h2>

<div class="spacer"></div>

<div class="sendui-standard-content">

    <div class="settings">
    {form $customer_settings, 'sendui~account:save'}

    <div class="section-form sendui-bloc-simple ui-widget-header ui-corner-all">
        <h3>Vos informations</h3>
    </div>

    <div class="bloc-form ui-corner-all">
        <div>{ctrl_label 'login'}</div>
        <p>{ctrl_control 'login'}</p>
    </div>

    <div class="bloc-form ui-corner-all">
        <div>{ctrl_label 'email'}</div>
        <p>{ctrl_control 'email'}</p>
    </div>

    <div class="bloc-form ui-corner-all">
        <div>{ctrl_label 'lastname'}</div>
        <p>{ctrl_control 'lastname'}</p>
    </div>

    <div class="bloc-form ui-corner-all">
        <div>{ctrl_label 'firstname'}</div>
        <p>{ctrl_control 'firstname'}</p>
    </div>

    <div class="bloc-form ui-corner-all">
        <div>{ctrl_label 'company'}</div>
        <p>{ctrl_control 'company'}</p>
    </div>

    <!--<div class="section-form sendui-bloc-simple ui-widget-header ui-corner-all">
        <h3>Changer votre mot de passe</h3>
    </div>-->

    <div class="section-form sendui-bloc-simple ui-widget-header ui-corner-all">
        <h3>Votre adresse</h3>
    </div>

    <div class="bloc-form ui-corner-all">
        <div>{ctrl_label 'address'}</div>
        <p>{ctrl_control 'address'}</p>
    </div>

    <div class="bloc-form ui-corner-all">
        <div>{ctrl_label 'zip'}</div>
        <p>{ctrl_control 'zip'}</p>
    </div>

    <div class="bloc-form ui-corner-all">
        <div>{ctrl_label 'city'}</div>
        <p>{ctrl_control 'city'}</p>
    </div>

    <div class="bloc-form ui-corner-all">
        <div>{ctrl_label 'country'}</div>
        <p>{ctrl_control 'country'}</p>
    </div>

    <div class="section-form sendui-bloc-simple ui-widget-header ui-corner-all">
        <h3>Interface</h3>
    </div>

    <div class="bloc-form ui-corner-all">
        <div>{ctrl_label 'theme'}</div>
        <p>{ctrl_control 'theme'}</p>
    </div>

    <p><input name="_submit" id="jforms_sendui_message_settings__submit" class="jforms-submit fg-button ui-state-default ui-corner-all" value="Enregistrer" type="submit"></p>

    {/form}
    </div>
</div>
