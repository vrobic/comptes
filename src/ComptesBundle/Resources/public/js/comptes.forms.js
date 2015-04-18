window.forms = function()
{
    // Select2 *****************************************************************
    $('form table.table td.input-select2 select').select2();

    // Écouteurs ***************************************************************

    // Confirmation de suppression
    $('form :submit[value="delete"]').click(function(){
        return confirm("Supprimer ?");
    });

    // Cocher/décocher tout dans les listes
    $('form table [data-trigger="check-all"], form [data-trigger="uncheck-all"]').click(function(e){

        e.preventDefault();

        var targetName = $(this).data('target-name'),
            check = $(this).data('trigger') === 'check-all';

        $(this).closest('table').find('input[name="'+targetName+'"]').prop('checked', check);
    });

    // Ajouter une nouvelle ligne
    $('table [data-trigger="add-new-row"]').click(function(e){

        e.preventDefault();

        // La ligne à copier
        var row = $(this).closest('table').find('tr.new-row[data-id].hidden:last'),
            id = parseInt(row.data('id'), 10); // Base 10

        // Détruit les select2 car ils ne peuvent pas être clônés
        var selects = $('td.input-select2 select', row);
        selects.select2('destroy');

        // La nouvelle ligne
        var newRow = row.clone(),
            newId = id - 1;

        // Modification et ajout de la nouvelle ligne
        $('input[name="batch[]"]', newRow).prop('checked', true);
        newRow = changeRowId(newRow, id.toString(), newId.toString());
        row.before(newRow);
        newRow.removeClass('hidden');

        // Réinstancie select2
        selects.select2();
        $('td.input-select2 select', newRow).select2();
    });

    /**
     * Modifie les identifiants et noms des champs et libellés contenus dans une ligne de tableau.
     *
     * @param {object} row La ligne conteneur
     * @param {string} id Identifiant à remplacer
     * @param {string} newId Nouvel identifiant
     * @returns {object} L'objet row modifié
     */
    function changeRowId(row, id, newId)
    {
        row.attr('data-id', newId);

        $('input[name="batch[]"]', row).val(newId);

        $(':input', row).each(function(){

            var inputId = $(this).attr('id'),
                inputName = $(this).attr('name');

            if (inputId !== undefined)
            {
                var newInputId = inputId.replace('-'+id, '-'+newId);
                $(this).attr('id', newInputId);
            }

            if (inputName !== undefined)
            {
                var newInputName = inputName.replace('['+id+']', '['+newId+']');
                $(this).attr('name', newInputName);
            }
        });

        $('label', row).each(function(){

            var labelFor = $(this).attr('for');

            if (labelFor !== undefined)
            {
                var newLabelFor = labelFor.replace('-'+id, '-'+newId);
                $(this).attr('for', newLabelFor);
            }
        });

        return row;
    }
}