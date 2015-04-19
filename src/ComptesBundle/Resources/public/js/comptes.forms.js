window.forms = function()
{
    // Select2
    var select2Options = {};
    $('form table.table td.input-select2 select').css('width', '100%').select2(select2Options);

    // Mots-clés
    var tagEditorOptions = {
        delimiter: '|',
        sortable: false,
        placeholder: "Mots-clés"
    };
    $('form table.table td.input-tags input[type="text"]').tagEditor(tagEditorOptions);

    // Confirmation de suppression
    $('form :submit[value="delete"]').click(function(){
        return confirm("Supprimer ?");
    });

    // Cocher/décocher tout dans les listes
    $('form table [data-trigger="check-all"], form table [data-trigger="uncheck-all"]').click(function(e){

        e.preventDefault();

        var targetName = $(this).data('target-name'),
            check = $(this).data('trigger') === 'check-all';

        $(this).closest('table').find('input[name="'+targetName+'"]').prop('checked', check);
    });

    // Ajouter une nouvelle ligne dans les listes
    var linesAdded = 0;

    $('form table [data-trigger="add-new-row"]').click(function(e){

        e.preventDefault();

        linesAdded++;

        // La ligne modèle
        var row = $(this).closest('table').find('tr.new-row[data-id].hidden:last'),
            id = parseInt(row.data('id'), 10); // Base 10

        // Les select2 et tagEditor existants
        var selects = $('td.input-select2 select', row),
            tags = $('td.input-tags input[type="text"]', row);

        // Sont détruits car ils ne peuvent pas être clônés
        selects.select2('destroy');
        tags.tagEditor('destroy');

        // La nouvelle ligne
        var newRow = row.clone(),
            newId = id - linesAdded;

        // Préparation de la nouvelle ligne
        newRow.attr('data-id', newId);
        $('input[name="batch[]"]', newRow).val(newId).prop('checked', true);

        // Remplace les attributs 'id', 'name' et 'for' des :input et label
        $(':input, label', newRow).each(function()
        {
            $(this).attr('id', function(){
                return $(this).attr('id') !== undefined ? $(this).attr('id').replace('-'+id, '-'+newId) : null;
            }).attr('name', function(){
                return $(this).attr('name') !== undefined ? $(this).attr('name').replace('['+id+']', '['+newId+']') : null;
            }).attr('for', function(){
                return $(this).attr('for') !== undefined ? $(this).attr('for').replace('-'+id, '-'+newId) : null;
            });
        });

        // Ajoute et affiche la ligne
        row.before(newRow);
        newRow.removeClass('hidden');

        // Réinstancie les select2 et tagEditor sur les deux lignes
        $('td.input-select2 select', newRow).add(selects).select2(select2Options);
        $('td.input-tags input[type="text"]', newRow).add(tags).tagEditor(tagEditorOptions);
    });
}