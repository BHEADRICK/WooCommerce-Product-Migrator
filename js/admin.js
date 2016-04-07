

//Surround this with jQuery(document).ready() if this isn't in the footer
(function($) {
    var data = {
        action: 'get_products_count'
    };
    var totalprods = 0;
    var prodpages = 0;
    var status = 'stopped';
	$.post(ajaxurl, data, function(resp){
        if(typeof resp == 'string'){
            resp = JSON.parse(resp);

            totalprods = resp.count;
            prodpages = Math.round(totalprods/10);

            console.log(prodpages + ' pages!');

            $('#productsResults .status').html('Found ' + totalprods + ' products!');

                    data.page = 1;

                   // getProducts(data);

        }
        console.log(resp);
    })
     $('#buttonRow .start').click(function(e){
         e.preventDefault();
          data.action= 'get_products';

         status = 'running';
         $('#productsResults .status').html('running!')
         getProducts(data);
     })

    $('#buttonRow .stop').click(function(e){
        e.preventDefault();
        status = 'stopped';
    })

    function getProducts(data){
      return  $.post(ajaxurl, data, function(resp){
            if(typeof resp == 'string'){
                resp = JSON.parse(resp);
            }

          console.log(prodpages + ' pages!');

            console.log(resp)
          $('#productsResults .pageof').html('page ' + data.page + ' of ' + prodpages);
          for(var i = 0; i<resp.products.length; i++){

                  var found = resp.products[i].found? '<a href="/wp-admin/post.php?post=' + resp.products[i].foundid + '&action=edit" target="_BLANK">Found!</a>':'Adding!';

                  if(resp.products[i].found)
                  found += resp.products[i].updating? ' (updating)' : ' (Up to date)';

                  $('#productsResults ul').append(
                      '<li>' + resp.products[i].title + ' ('+ resp.products[i].type + ') <span class="found"> ' + found +'</span> </li>'
                  )

                


              
              
          }
          $('#productsResults ul').scrollTop( $('#productsResults ul')[0].scrollHeight);


              if(data.page<prodpages){
                  data.page++;
                  console.log(data);

                  if(status=='running')
                  getProducts(data);
                  else{
                      $('#productsResults .status').html('stopped!')
                  }
              }



        }).fail(function(resp) {
          console.log(resp);
          $('#productsResults .status').html('Oops! We hit an error!').addClass('error');

      })
    }
	// $ Works! You can test it with next line if you like
	// console.log($);
	
})( jQuery );