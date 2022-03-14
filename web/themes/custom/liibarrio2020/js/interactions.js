(function ($, Drupal) {
  
    Drupal.behaviors.liibarrio2020_interactions = {
        attach: function (context, settings) {
        
            //Sets a vh custom property to account for the shifting vh in mobile
            setDocHeight();
            window.addEventListener('resize', setDocHeight);
            window.addEventListener('orientationchange', setDocHeight);

            function setDocHeight(){
                document.documentElement.style.setProperty('--vh', `${window.innerHeight/100}px`);
            }

            /*sets a custom property so that we can position the sidebar below the content top section*/
            setContentTopHeight();

            function setContentTopHeight(){
                const contentTop = document.querySelector('.content-top')?.offsetHeight;
                if(contentTop){
                    document.documentElement.style.setProperty('--content-top_height', `${contentTop}px`);
                }
            }
            /*Toggles the advanced search on the home page*/
            const [searchButton] = once('searchswitch',document.querySelector('#search-switch'));
           
            const searchBlock = document.querySelector('.search-wrapper');
            if(searchBlock){
               //searchSwitch.removeEventListener('click',toggleAdvancedSearch);
               searchButton.addEventListener('click',toggleAdvancedSearch);
            }
            function toggleAdvancedSearch(e){
                searchBlock.classList.toggle('show-advanced-search');
                toggleAria(e.currentTarget,"<h5>Advanced Search</h5>","<h5>General Search</h5>"); 
            }

            /*Toggles the sidebar on mobile*/
            const [sidebarToggle] = once('sbToggle', document.querySelector('#toggle_filters')); 
        
            const sidebar = document.querySelector('#sidebar_first');
            if(sidebar){
                sidebar.classList.remove('slide-in');
            }

            if(sidebarToggle){
                sidebarToggle.addEventListener('click',toggleSidebar);
            }

            const removeFilters = once('rmFilters',document.querySelectorAll('.btn-tab'));
            if(removeFilters.length>0){
                removeFilters.forEach(button=>{
                    button.addEventListener('click',removeFilterForm);
                })
            }

            function toggleSidebar(e){
                e.preventDefault();
                
                if(sidebar.classList.contains('slide-in')){
                    sidebar.classList.remove('slide-in');
                }
                else{
                    sidebar.classList.add('slide-in');
                }
                let word = 'Filters';
                if(sidebarToggle.innerText.includes('Details')){
                    word = 'Details';
                }
                toggleAria(sidebarToggle,`Open ${word}`, `Close ${word}`);
                document.addEventListener('scroll',sidebarOnScroll);
            }
            
            function sidebarOnScroll(e){
                sidebar.classList.remove('slide-in');
                sidebarToggle.setAttribute( 'aria-expanded', 'false' );
                let word = 'Filters';
                if(sidebarToggle.innerText.includes('Details')){
                    word = 'Details';
                }
                sidebarToggle.innerHTML=`Open ${word}`;
                document.removeEventListener('scroll',sidebarOnScroll);
            }

            function toggleAria(el,text, textAlt){
                if ( el.getAttribute( 'aria-expanded' ) === 'true' ) {
                    el.setAttribute( 'aria-expanded', 'false' );
                    if(text){
                        el.innerHTML=text;
                    }
                } else {
                    el.setAttribute( 'aria-expanded', 'true' );
                    el.innerHTML=textAlt;
                }
            }
            function removeFilterForm(e){
                const id = e.currentTarget.dataset.id;
                const targetInput = document.querySelector(`#${id}`);
                targetInput.checked=false;
                targetInput.form.submit();
            }

            const viewHeader = document.querySelectorAll('.view-header');
            if(viewHeader.length>0){
                viewHeader.forEach((header)=>{
                    if(header.nextElementSibling.classList.contains('view-empty')){
                       header.querySelector('.open-filter').remove();
                       document.querySelector('.fieldset-wrapper').remove();
                    }
                });
            }

        
        }
    };
})(jQuery, Drupal);