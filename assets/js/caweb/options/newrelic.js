/* CAWeb VIP New Relic Javascript */
jQuery(document).ready(function($) {
	$('#caweb-vip-options-form #add-apm-service').on('click', function(e){
        var site = $('select[name="caweb_vip_sites"]').find(":selected");

        if( "No Sites" !== site.val() ){
            var li = document.createElement('li');
            var input = document.createElement('input');
            var span = document.createElement('span');

            $(span).addClass('dashicons dashicons-minus align-middle mr-2 text-danger');
            $(span).on('click', remove_service );

            $(input).val( site.val() );
            $(input).attr('name', 'apm-services[]');
            $(input).attr('type', 'hidden');

            $(li).append(span);
            $(li).append(input);
            $(li).append(site.text());

            site.remove();
            $('#apm-services').append(li);

            if( ! $('select[name="caweb_vip_sites"] option').length ){
                $('select[name="caweb_vip_sites"]').append('<option>No Sites</option>');
            }

        }
    });

    $('#caweb-vip-options-form #apm-services span').on('click', remove_service );

    function remove_service(){
        var li = $(this).parent();
        var input = $(this).next();
        var option = document.createElement('option');

        $(this).remove();

        $(option).val($(input).val());

        $(input).remove();

        $(option).text($(li).text());

        $(li).remove();

        $('select[name="caweb_vip_sites"]').append(option);

        var no_site = $('select[name="caweb_vip_sites"] option:contains(No Sites)');

        if( no_site.length ){
            no_site.remove();
        }

        sort_sites();
    }

    function sort_sites(){
        var options = $('select[name="caweb_vip_sites"] option');

        options.sort(function(a,b){
            return a.value-b.value;
        });

        $('select[name="caweb_vip_sites"]').html(options);
    }
});