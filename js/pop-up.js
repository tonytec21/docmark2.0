let canCloseModal = true;

function showProcessingPopup() {
    // Criar o pop-up modal com a mensagem
    const modalHtml = `
        <div id="processing-modal" style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;


        ">
            <div style="
            position: relative;
            overflow-y: auto;
            max-width: 60%;
            margin: 10px auto 50px;
            padding: 90px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(6.2px);
            ">
                <p style="color: #fff;">Aguarde enquanto os arquivos são processados...</p>
                <button id="close-modal-btn" class="third" style="
                position: absolute;
                    top: 5px;
                    right: 5px;
                    color: #fff
                    font-size: 14px;
                    cursor: pointer;
                ">X</button>
            </div>
        </div>
    `;
    $('body').append(modalHtml);

    $('#close-modal-btn').on('click', function() {
    if (canCloseModal) {
        hideProcessingPopup();
    }
});

// Desativar o botão de fechar temporariamente por 5 segundos
canCloseModal = false;
setTimeout(function() {
    canCloseModal = true;
}, 5000); // 5000 milissegundos = 5 segundos
}

function hideProcessingPopup() {
    // Remover o pop-up modal
    $('#processing-modal').remove();
}

function showConfirmationPopup() {
    // Mostrar pop-up de confirmação após o processamento
    const confirmationHtml = `
        <div id="confirmation-modal" style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        ">
            <div style="
                padding: 20px;
                background-color: #f1f1f1;
                border: 1px solid #ccc;
                border-radius: 4px;
            ">
                <p>Arquivos chancelados e compactados com sucesso!</p>
            </div>
        </div>
    `;
    $('body').append(confirmationHtml);
}

function onSubmitForm() {
    showProcessingPopup();
    return true; // Retorna true para permitir o envio do formulário
}

function onProcessingComplete() {
    hideProcessingPopup();
    showConfirmationPopup();
}