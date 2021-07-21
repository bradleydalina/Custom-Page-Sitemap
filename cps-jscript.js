(function(w, d){
	function get_valid_element(){
		var cps_list = document.querySelectorAll('[cps-menu=true]');
		for(let i =0; i < cps_list.length; i++){
			if(cps_list[i].getAttribute("order").length > 0){
				ul_sort(cps_list[i], cps_list[i].getAttribute("order"));
			}else{
				ul_sort(cps_list[i], cps_order);
			}
		}
	}
	function ul_sort(obj=null, order='ASC') {  
		// Declaring Variables
		var cps_list, i, run, li, stop;
		// Taking content of list as input
		cps_list = obj;
		run = true;  
		while (run) {
			run = false;
			li = cps_list.getElementsByTagName("LI");
			if(order==='ASC'){
				// Loop traversing through all the list items
				for (i = 0; i < (li.length - 1); i++) {
					stop = false;
					if (li[i].innerHTML.toLowerCase() > 
						li[i + 1].innerHTML.toLowerCase()) {
						stop = true;
						break;
					}
				}
				/* If the current item is smaller than 
			   the next item then adding it after 
			   it using insertBefore() method */
				if (stop) {
					li[i].parentNode.insertBefore(li[i + 1], li[i]);						  
					run = true;
				}									
			}else{									
					 // Loop traversing through all the list items
					for (i = 0; i < (li.length - 1); i++) {
						stop = false;
						if (li[i].innerHTML.toLowerCase() < li[i + 1].innerHTML.toLowerCase()) {
							stop = true;
							break;
						}
					}
					/* If the current item is smaller than 
					   the next item then adding it after 
					   it using insertBefore() method */
					if (stop){
						li[i].parentNode.insertBefore(li[i + 1], li[i]);							  
						run = true;
					}
			}
		}

	}
	
	w.onload = get_valid_element();
})(window, document);

