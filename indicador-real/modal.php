<div class="modal-header">
    <h5 class="modal-title" id="modalBuscaLabel">Buscar Arquivos</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="modal-body">
    <form id="formBusca">
        <div class="form-group">
            <label for="dataInicial">Data Inicial:</label>
            <input type="date" id="dataInicial" name="dataInicial" required>
        </div>
        <div class="form-group">
            <label for="dataFinal">Data Final:</label>
            <input type="date" id="dataFinal" name="dataFinal" required>
        </div>
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>
    <div id="resultadoBusca"></div>
</div>
