// Original Mastodon share by codepo8.
// I got the key, I got the secret…
let key = 'mastodon-instance';
let instance = localStorage.getItem(key);
instance = ( null === instance ) ? '' : instance;

// get the link from the DOM
const button = document.querySelector('.mastodon-share');

// refresh the link with the instance name
const refreshlink = (instance) => {
	let url = isValidUrl( instance );
	if ( ! url ) {
		url = 'https://' + instance;
	} else {
		url = instance;
	}
    button.href = `${url}/share?text=${encodeURIComponent(document.title)}%0A${encodeURIComponent(location.href)}`;
}

// check whether stored value is a url
function isValidUrl( string ) {
	try {
		new URL(string);
		return true;
	} catch (error) {
		return false;
	}
}

// got it? Let's go! 
if (button) {
    // labels and texts from the link
    let prompt = button.dataset.prompt || 'Add your Mastodon instance, e.g. wptoots.social';
    let editlabel = button.dataset.editlabel || 'Edit your Mastodon instance';
    let edittext = button.dataset.edittext || '✏️';

    // Ask the user for the instance name and set it…
    const setinstance = _ => {
        instance = window.prompt(prompt, instance);
        if(instance) {
            localStorage.setItem(key, instance);
            createeditbutton();
            refreshlink(instance);
            button.click();
        }
    }
    
    // create and insert the edit link
    const createeditbutton = _ => {
        if (document.querySelector('button.mastodon-edit')) return;
        let editlink = document.createElement('button');
          editlink.innerText = edittext;
          editlink.classList.add('mastodon-edit');
          editlink.title = editlabel;
          editlink.ariaLabel = editlabel;
          editlink.addEventListener('click', (e) => {
              e.preventDefault();
              localStorage.removeItem(key);
              setinstance();
          });
          button.insertAdjacentElement('afterend', editlink);
    }
    
    // if there is  a value in localstorage, create the edit link
    if(localStorage.getItem(key)) {
        createeditbutton();
    }  
    
    // When a user clicks the link
    button.addEventListener('click', (e) => {

        // If the user has already entered their instance 
        // and it is in localstorage write out the link href 
        // with the instance and the current page title and URL
        if(localStorage.getItem(key)) {
            refreshlink(localStorage.getItem(key));
            // otherwise, prompt the user for their instance and save it to localstorage
        } else {
            e.preventDefault();
            setinstance();
        }

    });
}