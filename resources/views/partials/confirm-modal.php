<div class="app-confirm-overlay" id="appConfirmModal" role="dialog" aria-modal="true" aria-labelledby="appConfirmModalTitle" aria-hidden="true">
    <div class="app-confirm-dialog">
        <div class="app-confirm-modal">
            <div class="app-confirm-header">
                <div class="modal-title-wrap">
                    <span class="modal-warning-icon"><i data-feather="alert-triangle" aria-hidden="true"></i></span>
                    <div>
                        <h5 class="modal-title" id="appConfirmModalTitle">Confirm action</h5>
                        <p class="modal-subtitle mb-0" id="appConfirmModalSubtitle">Please review the effect before continuing.</p>
                    </div>
                </div>
                <button type="button" class="app-confirm-close" data-confirm-cancel aria-label="Close"><i data-feather="x" aria-hidden="true"></i></button>
            </div>
            <div class="app-confirm-body">
                <p class="confirm-message mb-0" id="appConfirmModalMessage">This action may affect existing records.</p>
            </div>
            <div class="app-confirm-footer">
                <button type="button" class="action-button action-button--neutral btn btn-outline-secondary" data-confirm-cancel><i data-feather="x" aria-hidden="true"></i>Cancel</button>
                <button type="button" class="action-button action-button--danger btn btn-danger" id="appConfirmModalConfirm"><i data-feather="check-circle" aria-hidden="true"></i><span data-confirm-button-label>Continue</span></button>
            </div>
        </div>
    </div>
</div>
