$(function()
{
    var modules = $('html').data('modules').split(';');
    
    for (var i = 0; i < modules.length; i++)
    {
        var module = modules[i];
        
        if (undefined === window[module])
        {
            continue;
        }
        
        window[module]();
    }
});