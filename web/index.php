<?php
    // load the settings from the .env file in the repo (ignored)
    require __DIR__ . '/vendor/autoload.php';
    $dotenv = new Dotenv\Dotenv(dirname(__DIR__));
    $dotenv->load();

    /***
     * runs a ruby script with the big commerce api calls. settings stored in web/settings.php
     *
     * successful messages returned in $output
     * errors returned in $errors
     *
     * routing like behaviour done using ?action='name' URL parameter
     *
     */

    $errors = array();
    $output = array();

    if($action = filter_input(INPUT_GET, "action", FILTER_SANITIZE_STRING)){

        // Call Ruby Script
        // runs the same script with or without the additional order_number param.
        if($action=='print_all' || $action=='print_single') {
            // escape any user input to avoid system commands being ran incorrectly
            // @note: currently order_number can only be an integer
            $order_number = "";
            if(!empty($_GET['order_number'])) {
                $order_number = filter_input(INPUT_GET, "order_number", FILTER_SANITIZE_NUMBER_INT);
                if(!$order_number){
                    $errors[] = "Order number is not valid";
                }
            }
            // add order_number as script input (if available)
            $command = sprintf('LC_ALL=en_GB.UTF-8 ruby %s/shop_download.rb %s %s 2>&1', dirname(__DIR__), $order_number, dirname($_SERVER['SCRIPT_FILENAME']));
            $last_line = exec($command,$output);

            if($last_line !== "Success...let's go surfing!"){
                $errors[] = array_pop($output);
            }
            echo(json_encode(array('errors'=>$errors,'command'=>$command,'last_line'=>$last_line,'output'=>$output)));
            header('Content-type: application/json');
            exit();
        // output useful information for debugging
        } elseif($action == 'debug') {
            $output[] = "IP ADDRESS = {$_SERVER['SERVER_ADDR']}";
            $output[] = "HOSTNAME = ".gethostname();
            $output[] = "WHOAMI = ".`whoami`;
            $output[] = "SCRIPT DIR = ".dirname(__DIR__);
            $output[] = "APACHE DIR = ".str_replace( '/index.php', '', $_SERVER['SCRIPT_FILENAME'] )."\n";
            $output[] = "RUBY VERSION = " . `ruby -v`;
            $output[] = "PHP VERSION = " . `php -r 'echo phpversion();'`;
            $output[] = "APACHE VERSION = " . `apache2 -v`;
            $output[] = "UBUNTU VERSION = " . `lsb_release -d`;
            $output[] = "LAST CREATED CSV = ".'<a href="order_addresses.csv">order_addresses.csv</a>';
            $output[] = "LAST CREATED PDF = ".'<a href="output.pdf">output.pdf</a>';

        // output phpinfo
        } elseif($action == 'phpinfo') {
            ob_start();
            phpinfo();
            $output_raw[] = ob_get_contents();
            ob_clean();

            $output_raw[] = "<style>
            body { font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif,'Apple Color Emoji','Segoe UI Emoji','Segoe UI Symbol','Noto Color Emoji'!important;}
            a { background:transparent!important; }
            h1 { font-size:2.5rem!important; }
            #ouput-raw h1 { font-size:1.5rem!important; }
            </style>";

        // get total awaiting approval via ruby script
        } elseif($action == 'orders') {
            $command = sprintf('ruby %s/awaiting_fulfillment.rb', dirname(__DIR__));
            $result = exec($command,$output);
            $jsonString = implode("\n",$output);
            $json = json_decode($jsonString,true);
            echo(json_encode($json));
            header('Content-type: application/json');
            exit();
        }
    }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Labels | Awaiting Fulfillment | Bigcommerce</title>
    <link href="assets/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
    <style>
    /* Sticky footer styles
    -------------------------------------------------- */
    html {
        position: relative;
        min-height: 100%;
    }
    body {
        margin-bottom: 4rem; /* Margin bottom by footer height */
    }
    .footer {
        position: absolute;
        bottom: 0;
        width: 100%;
        height: 4rem; /* Set the fixed height of the footer here */
        line-height: 4rem; /* Vertically center the text there */
        background-color: #f5f5f5;
    }
    .loader {
        border: 1rem solid #f3f3f3; /* Light grey */
        border-top: 1rem solid #3498db; /* Blue */
        width: 4rem;
        height: 4rem;
        animation: spin 1s cubic-bezier(.8,.2,.2,.8) infinite;
    }
    dl > * {padding: .5rem 0;margin:0}
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    </style>
</head>
<body>
    <header>
        <div class="collapse bg-dark" id="navbarHeader">
            <div class="container">
                <div class="row">
                    <div class="col-sm-8 col-md-7 py-4">
                        <h4 class="text-white">About</h4>
                        <p class="text-muted">Download BigCommerce data to produce delivery lables for orders awaiting fulfillment.</p>
                    </div>
                    <div class="col-sm-4 offset-md-1 py-4">
                        <h4 class="text-white">Links</h4>
                        <ul class="list-unstyled">
                            <li><a href="<?php echo str_replace( 'index.php', '', $_SERVER['PHP_SELF'] ) ?>" class="text-white">Reload</a></li>
                            <li><a href="https://github.com/openenergymonitor/BigCommerceAPI_OrderAddressLabelPrint" target="_blank" class="text-white">Github</a></li>
                            <li><a href="?action=debug" class="text-white">Debug</a></li>
                            <li><a href="?action=phpinfo" class="text-white">PHP info</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="navbar navbar-dark bg-dark shadow-sm">
            <div class="container d-flex justify-content-between">
                <a href="https://www.bigcommerce.com/" class="navbar-brand d-flex align-items-center">
                    <img src="assets/BigCommerce-logomark-light.svg" style="height:2em;margin-right:.5em">
                    <strong>BigCommerce</strong>
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarHeader" aria-controls="navbarHeader" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
        </div>
    </header>

    <main role="main" class="mb-4">


        <section class="jumbotron text-center">
            <div class="container">

                <h1 class="jumbotron-heading"><a class="text-body" href="<?php echo str_replace( 'index.php', '', $_SERVER['PHP_SELF'] ) ?>">Address Labels</a></h1>
                <p class="lead text-muted">Print all labels awaiting fulfillment, or print individual label based on order number.</p>

                <div class="btn-group" role="group">
                    <form onsubmit="printLabels(event)" class="mr-1">
                        <input type="hidden" name="action" value="print_all">
                        <button id="printAllButton" class="btn btn-lg btn-primary">Print All</button>
                    </form>
                    <form onsubmit="printLabels(event)">
                        <input type="hidden" name="action" value="print_single">
                        <div class="input-group input-group-lg mb-0">
                            <input type="search" pattern="\d*" name="order_number" class="form-control text-right" placeholder="Order Number" required value="<?php echo $order_number ?>" title="Must be an Order Number">
                            <div class="input-group-append" id="button-addon4">
                                <button class="btn btn-outline-secondary">Print One</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div id="ajax-loader" class="d-none mx-auto loader mt-4 rounded-circle"></div>

                <div id="output-raw" class="<?php echo empty($output_raw) ? 'd-none':''; ?> mb-0 mt-4"><?php echo implode("\n",$output_raw); ?></div>

                <div class="col-sm-10 col-md-7 col-xl-6 mx-auto text-left">
                    <pre id="output" class="<?php echo empty($output) ? 'd-none':''; ?> mb-0 mt-4"><?php echo implode("\n",$output); ?></pre>
                    <h4 id="errors-title" class="<?php echo empty($errors) ? 'd-none':''; ?> mt-4 mb-0">Error:</h4>
                    <pre id="errors" class="<?php echo empty($errors) ? 'd-none':''; ?> mb-0 mt-4"><?php echo implode("\n",$errors); ?></pre>
                </div>

            </div>
        </section>



        <section id="list">
            <div class="container">
                <h4 id="list_title" class="text-center"></h4>
                <div id="orders-list" class="list-group text-left col-sm-10 col-md-9 col-lg-7 p-0 pb-5 mx-auto"></div>
                <template id="orders-list-items">
                    <a href="#" onclick="selectOrder(event,this)" class="list-group-item list-group-item-action flex-column align-items-start">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">
                                <small class="country_code badge badge-secondary badge-pill"></small>
                                <span class="customer_name"></span>
                                <br class="d-sm-none">
                                <small class="date_created ml-2 text-secondary"></small>
                            </h5>
                            <h4 class="order_number text-primary"></h4>
                        </div>
                        <blockquote class="mb-1">
                            <p class="mb-0 customer_message"></p>
                            <footer class="email blockquote-footer"></footer>
                        </blockquote>
                        <div class="moreInfo d-none">
                            <p class="text-center"><small class="text-info"><em>(Order number copied to search box)</em></small></p>
                            <dl class="row text-info">
                                <dt class="col-5 text-sm-right border-bottom">Customer Name</dt><dd class="col-7 border-bottom full_name"></dd>
                                <dt class="col-5 text-sm-right border-bottom">Total Ex. Tax</dt><dd class="col-7 border-bottom ex_tax"></dd>
                                <dt class="col-5 text-sm-right border-bottom">Shipping Cost</dt><dd class="col-7 border-bottom shipping"></dd>
                                <dt class="col-5 text-sm-right border-bottom">Shipping Country</dt><dd class="col-7 border-bottom country"></dd>
                                <dt class="col-5 text-sm-right border-bottom">Customer Email</dt><dd class="col-7 border-bottom email"></dd>
                                <dt class="col-5 text-sm-right border-bottom">Customer Phone</dt><dd class="col-7 border-bottom phone"></dd>
                                <dt class="col-5 text-sm-right border-bottom">Date Created</dt><dd class="col-7 border-bottom date_created"></dd>
                                <dt class="col-5 text-sm-right border-bottom">Staff Notes</dt><dd class="col-7 border-bottom notes"></dd>
                            <dl>
                        </div>
                    </a>
                </template>
            </div>
        </section>

    </main>

    <footer class="text-muted footer">
        <div class="container">
            <p class="text-center mb-0"><abbr title="GNU GPL">🄯</abbr> OpenEnergyMonitor. Source on <a href="https://github.com/openenergymonitor"> on Github</a>.</p>
        </div>
    </footer>

    <script src="assets/jquery-3.3.1.min.js"></script>
    <script src="assets/popper.min.js"></script>
    <script src="assets/bootstrap.min.js"></script>
    <script src="assets/moment.min.js"></script>
    <script>

        var xhr,
            reloadButton = '<button class="btn btn-outline-primary" onclick="getOrderAwaitingFulfillment()">Reload</button>',
            cancelButton = '<btn id="cancel" onclick="cancelDownload()" title="Cancel Download&hellip;" class="btn btn-outline-secondary">Loading list&hellip;</btn>',
            loadButton = '<button class="btn btn-outline-primary" onclick="getOrderAwaitingFulfillment()">Get List of Orders Awaiting Fulfillment</button>',
            listTitle = document.getElementById('list_title'),
            list = document.getElementById('orders-list'),
            printAllButton = document.getElementById('printAllButton'),
            listItemTemplate = document.getElementById('orders-list-items'),
            ajaxLoader = document.getElementById('ajax-loader'),
            output = document.getElementById('output'),
            outputRaw = document.getElementById('output-raw'),
            errors = document.getElementById('errors'),
            errorsTitle = document.getElementById('errors-title')

        function getOrderAwaitingFulfillment(){
            clearList()
            listTitle.innerHTML = cancelButton;
            // get the details regarding the orders via an AJAX call
            xhr = new XMLHttpRequest()
            xhr.open('GET', '?action=orders')
            xhr.setRequestHeader('Content-Type', 'application/json')
            xhr.onload = function() {
                // on success (200) add data to DOM
                if (xhr.status === 200) {
                    // parse the JSON response
                    let orderStatus = JSON.parse(xhr.responseText)
                    // if any orders Awaiting Fulfillment display as list
                    if (orderStatus.orders.length>0) {
                        // add tooltip value to "Print All" button
                        printAllButton.title = orderStatus.orders.length + ' orders ' + orderStatus.name
                        // output a tile with number of orders in list
                        listTitle.innerHTML = 'Orders '+ orderStatus.name + ': ('+orderStatus.orders.length+')'+ ' '+reloadButton
                        // loop through all the orders and copy the <template> tag for every order
                        orderStatus.orders.forEach(function (order) {
                            // console.log(order)
                            listItem = document.createElement('div')
                            // get HTML template from DOM <template> tag
                            listItem.innerHTML = listItemTemplate.innerHTML
                            // add order data to HTML
                            listItem.querySelector('.customer_message').innerText = order.customer_message
                            listItem.querySelector('.email').innerText = order.billing_address.email
                            listItem.querySelector('.country_code').innerText = order.geoip_country_iso2
                            listItem.querySelector('.customer_name').innerText = order.billing_address.last_name +', '+order.billing_address.first_name.substr(0,1)
                            listItem.querySelector('.order_number').innerText = order.id
                            listItem.querySelector('.date_created').innerText = moment(order.date_created).fromNow()
                            // add hover data
                            listItem.querySelector('.customer_name').title = order.billing_address.last_name +', '+order.billing_address.first_name + ' | Ex. Tax= '+format_currency(order.subtotal_ex_tax, order.currency_code) + ' | shipping= '+format_currency(order.shipping_cost_inc_tax)
                            listItem.querySelector('.country_code').title = order.geoip_country
                            listItem.querySelector('.date_created').title = moment(order.date_created).format('llll')
                            // add the more info dropdown
                            listItem.querySelector('dl .full_name').innerText = order.billing_address.first_name +' '+order.billing_address.last_name
                            listItem.querySelector('dl .ex_tax').innerText = format_currency(order.subtotal_ex_tax, order.currency_code)
                            listItem.querySelector('dl .shipping').innerText = format_currency(order.shipping_cost_inc_tax)
                            listItem.querySelector('dl .country').innerText = order.geoip_country
                            listItem.querySelector('dl .email').innerText = order.billing_address.email
                            listItem.querySelector('dl .phone').innerText = order.billing_address.phone
                            listItem.querySelector('dl .notes').innerText = order.staff_notes
                            listItem.querySelector('dl .date_created').innerText = moment(order.date_created).format('llll')
                            // add to the DOM
                            list.appendChild(listItem.firstElementChild) // dont include the container DIV
                            // save the order object as elem data
                            // elem.dataset[prop] must be string (use JSON to serialize object)
                            list.lastElementChild.dataset.order = JSON.stringify(order)
                            // animate
                            showList()
                        })
                    } else {
                        listTitle.innerHTML = 'No orders ' + orderStatus.name + ' '+reloadButton
                    }
                }
            }
            // start the AJAX request
            xhr.send()
        }
        // show the button to load the list
        listTitle.innerHTML = loadButton

        // use a symbol for the recognized formats
        function format_currency(amount, currency) {
            currency = currency || 'GBP'
            let symbols = {GBP: '£', EUR: '€', USD: '$'}
            return (symbols[currency]||'') + Number(amount).toFixed(2) + (!symbols[currency] ? ' ('+currency+')': '')
        }
        // stop the AJAX request if cancel button pressed
        function cancelDownload(){
            xhr.abort()
            listTitle.innerHTML = 'Download cancelled  '+reloadButton
            clearList()
        }
        // animate the list when cleared and added to
        function clearList(){ $(list).slideUp('slow', function(){ $(this).html('') }) }
        function showList(){ $(list).slideDown('fast') }

        //populate the search field on click of the list items
        function selectOrder(event,elem){
            // recall the saved order (elem.dataset[prop] must be text)
            let order = JSON.parse(elem.dataset.order)
            // prevent link from navigating the page
            event.preventDefault()

            // prefill the search box with the order's id
            document.querySelector('[name="order_number"]').value = order.id

            // show/hide more information
            if(moreInfo = elem.querySelector('.moreInfo.d-none')){
                // remove the d-none class and use jquery to slide down
                $(moreInfo).removeClass('d-none').hide().slideDown('fast')
            } else {
                // use jquery to slide up/down
                $(elem.querySelector('.moreInfo')).stop().slideToggle('fast')
            }
        }
        // display ajax response and removes the css class that hides the element
        function showResponse(elem,arr){
            if (!arr || arr.length == 0) return
            arr = arr.constructor == Array ? arr : [arr]
            elem.innerText = arr.join("\n")
            elem.classList.remove('d-none')
        }
        // hide the container that holds the content. clear out the content once hidden
        function hideResponse(elem){
            $(elem).slideUp('slow',function(){
                this.innerText = ''
                this.classList.add('d-none')
                this.style.display = 'block'
            })
        }
        // call the api scripts using ajax. uses loading animation to break up delay in executing system scripts.
        // called by form on submit
        function printLabels(event){
            event.preventDefault()
            // get the form data
            let formData = new FormData(event.target)
            // add the form inputs to the requested url
            let url = '?'+ new URLSearchParams(new FormData(event.target)).toString()
            // use the action value to call specific "action"
            if(action = formData.get('action')) {
                // hide & empty content containers
                hideResponse(output)
                hideResponse(outputRaw)
                hideResponse(errors)
                // hide & empty error messages
                errorsTitle.classList.add('d-none')
                errorsTitle.title = ''
                // slide in the loading animation after enough delay for the above content to be hidden
                setTimeout(function(){
                    $(ajaxLoader).removeClass('d-none').hide().slideDown('fast')
                },800);
                // the AJAX that calls the ?action="[action]" - see the PHP for what actions available
                xhr = new XMLHttpRequest()
                xhr.open('GET', url)
                // responses are JSON
                xhr.setRequestHeader('Content-Type', 'application/json')
                xhr.onload = function() {
                    // only act on success
                    if (xhr.status === 200) {
                        response = JSON.parse(xhr.responseText)
                        // hide loader
                        ajaxLoader.classList.add('d-none')
                        // show the response in the page
                        showResponse(output, response.output)
                        showResponse(outputRaw, response.output_raw)
                        showResponse(errors, response.errors)
                        // show errors if any returned
                        if (response.errors && response.errors.length>0) {
                            errorsTitle.classList.remove('d-none')
                            errorsTitle.title = 'Error running command: '+response.errors.command
                            console.log(errorsTitle)
                        }
                    }
                }
                xhr.send();
            }
        }
    </script>
</body>
</html>