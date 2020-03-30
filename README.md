## Building an SMS Based Bot to Track Covid-19

It's no news that the [Novel Coronavirus](https://en.wikipedia.org/wiki/2019%E2%80%9320_coronavirus_pandemic) which started in  Wuhan, China has since spread around the world and today it's been declared a pandemic by the [World Health Organization (W.H.O)](https://www.who.int/emergencies/diseases/novel-coronavirus-2019) and at such the need to stay informed about the virus is very important. Luckily, information concerning the virus can easily be found on the internet either through a simple Google search or on social media platforms. However, not everyone has easy access to the internet. In some third world countries like [Nigeria](https://en.wikipedia.org/wiki/Nigeria) and in a situation where staying indoors helps to prevent the widespread of the virus, it gets even more difficult to get access to information(internet). Fortunately, text messages are also a good form of communication that doesn't rely on one having internet connectivity.

In this tutorial, you will build a simple SMS based bot to help get information about the Novel Coronavirus cases in a country using [Twilio Programmable SMS](https://www.twilio.com/sms).

## Prerequisite

To follow through with this tutorial, you will need the following:

- Basic knowledge of Laravel
- [Laravel](https://laravel.com/docs/master) Installed on your local machine
- [Composer](https://getcomposer.org/) globally installed
- [Twilio Account](https://www.twilio.com/referral/B2YAW1)

## Project setup

This tutorial will make use of Laravel. To get started building out your bot, you need to first create a new Laravel project. Open up your terminal and run the following command to generate a new Laravel application using the [Laravel installer](https://laravel.com/docs/7.x#installing-laravel):

    $ laravel new covid19-bot

**NOTE:** *the Laravel installer needs to be installed on your PC for the above command to work. If you don't have it then head over to the [official Laravel documentation](https://laravel.com/docs/7.x) to see how to.*

Next, point your terminal working directory to the just created project and run the following command to install the Twilio PHP SDK which will be used for sending out SMS from the application:

    $ cd covid19-bot
    $ composer require twilio/sdk

Now you need to get your Twilio credentials which will be used for authorizing requests made using the Twilio PHP SDK. Head to your [Twilio dashboard](https://www.twilio.com/console/) and copy out your *account sid* and *auth token:*

![https://res.cloudinary.com/brianiyoha/image/upload/v1584495960/Articles%20sample/Group_18.png](https://res.cloudinary.com/brianiyoha/image/upload/v1584495960/Articles%20sample/Group_18.png)

Next, head to the *[phone numbers](https://www.twilio.com/console/phone-numbers/incoming)* section and copy out your active Twilio phone number:

![https://res.cloudinary.com/brianiyoha/image/upload/v1584496232/Articles%20sample/Group_2_2.png](https://res.cloudinary.com/brianiyoha/image/upload/v1584496232/Articles%20sample/Group_2_2.png)

Now you have to keep this credentials safe in your application. To do this open up your `.env` file in project root directory and add the following variables to store them as environment variables:

    TWILIO_ACCOUNT_SID=YOUR ACCOUNT SID
    TWILIO_AUTH_TOKEN=YOUR AUTH TOKEN
    TWILIO_PHONE_NUMBER=YOUR TWILIO PHONE NUMBER

## Building Bot Logic

Now that you have successfully set up your Laravel project, let's jump into writing out the application logic. Start off by first creating a [Controller](https://laravel.com/docs/7.x/controllers#introduction) that will hose the application business logic. Open up a terminal in the project directory and run the following command to generate a new controller class:

    $ php artisan make:controller BotController

Next, open up the newly created controller class (`app/Http/Controllers/BotController.php`) and make the following changes:

    <?php
    
    namespace App\Http\Controllers;
    
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Str;
    use Twilio\Rest\Client;
    
    class BotController extends Controller
    {
        public function checkCountryStatus(Request $request)
        {
            $from = $request->input("From");
            $body = $request->input("Body");
            $response = Http::get("https://corona.lmao.ninja/countries/{$body}");
            $response = json_decode($response->body());
            if (isset($response->message)) {
                $this->sendMessage($response->message, $from);
                return;
            }
    
            $message = "👋 Here's the summary of the Covid-19 cases in " . Str::title($body) . " as at " . now()->toRfc850String() . "\n\n";
            $message .= "Today Cases: {$response->todayCases} \n";
            $message .= "Recovered Cases: {$response->recovered} \n";
            $message .= "Deaths Recorded: {$response->deaths} \n";
            $message .= "Total Cases: {$response->cases} \n";
    
            $this->sendMessage($message, $from);
            return;
        }
    
        /**
         * Sends sms to user using Twilio's programmable SMS client
         * @param string $message Body of sms
         * @param string $recipient string of phone number of recipient
         */
        private function sendMessage($message, $recipient)
        {
            $account_sid = getenv("TWILIO_ACCOUNT_SID");
            $auth_token = getenv("TWILIO_AUTH_TOKEN");
            $twilio_number = getenv("TWILIO_PHONE_NUMBER");
            $client = new Client($account_sid, $auth_token);
            return $client->messages->create($recipient, ['from' => $twilio_number, 'body' => $message]);
        }
    }

Let's break down what is happening in the code above. The `checkCountryStatus()` function is the main function here which handles what happens when a new message is sent to your Twilio number. The phone number of the sender is gotten from the `From` property of the request body followed by the `Body` property which is the name of a country the sender would like to get information about. Next, using the new [HTTP Client](https://laravel.com/docs/7.x/http-client) from Laravel, a GET request is made to [https://github.com/novelcovid/api](https://github.com/novelcovid/api)  - an open-source API for tracking COVID-19:

     $from = $request->input("From");
     $body = $request->input("Body");
     $response = Http::get("https://corona.lmao.ninja/countries/{$body}");
          

The country name (`$body`) is passed to the API URL as a path parameter. Next, using the PHP `json_decode` method, the response from the API is stored as a JSON object and a message is sent back to the user using the `sendMessage()` helper function depending on the response gotten from the request.  The `sendMessage()`  method takes in two arguments; the *message* and the *recipient.* In the `sendMessage()` function, a new instance of the *Twilio Client* is instantiated with the credentials retrieved from the environment variables which was stored in an earlier section of this tutorial:

    $account_sid = getenv("TWILIO_ACCOUNT_SID");
    $auth_token = getenv("TWILIO_AUTH_TOKEN");
    
    $client = new Client($account_sid, $auth_token);   

Next, using the instance of the Twilio Client, the `messages->create()` is called to actually send out an SMS to the recipient via the Twilio programmable SMS API. The `message->create()` method takes in two arguments; the `recipient` - the receiver of the message and an associative  array containing the `body` and `from` properties:

    $client->messages->create($recipient, ['from' => $twilio_number, 'body' => $message]);
        

In this case, the `from` number is your active Twilio phone number and the `body` is the response to be sent back to the initial sender of the message.

## Creating Route

Having written out the application logic, you need to create an entry point to your application. TO do this, open up the `routes/api.php` file and make the following changes:

    <?php
    
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;
    
    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
    */
    
    Route::middleware('auth:api')->get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/bot','BotController@checkCountryStatus');

## Setting up Twilio Webhook

Before you can move on to testing the bot, you must first update your Twilio phone number webhook settings with a publicly accessible route to your application that enables Twilio to know what to do whenever a new message is received. 

### Making the application publicly accessible

To allow access to your Laravel project through a webhook, your application has to be accessible via the internet and this can easily be done using [ngrok](https://ngrok.com/).

If you don’t have [ngrok](https://ngrok.com/) already set up on your computer, you can quickly do so by following the instructions on their [official download page](https://ngrok.com/download). If you already have it set up then open up your terminal and run the following commands to start your Laravel application and expose it to the internet:

    $ php artisan serve

Take note of the port your application is currently running on (usually `8000`). Next, while still running the above command, open another instance of your terminal and run this command:

    $ ngrok http 8000

After successful execution of the above command, you should see a screen like this:

![https://camo.githubusercontent.com/b81d4c4aa2104e5545f8fa48269c578454960697/68747470733a2f2f70617065722d6174746163686d656e74732e64726f70626f782e636f6d2f735f463742413245463337393739433442463434423541413142393230374438443345433945444445323746423944373130444443393944443242434234373333385f313536303637323039383733315f53637265656e73686f742b66726f6d2b323031392d30362d31362b30382d35372d32382e706e67](https://camo.githubusercontent.com/b81d4c4aa2104e5545f8fa48269c578454960697/68747470733a2f2f70617065722d6174746163686d656e74732e64726f70626f782e636f6d2f735f463742413245463337393739433442463434423541413142393230374438443345433945444445323746423944373130444443393944443242434234373333385f313536303637323039383733315f53637265656e73686f742b66726f6d2b323031392d30362d31362b30382d35372d32382e706e67)

**NOTE:**

- *Replace `8000` with the port your application is running on.*
- *Take note of the `forwarding` url as we will be making use of it next.*

### Updating Twilio phone number configuration

Now navigate to the [active phone number](https://www.twilio.com/console/phone-numbers/incoming) section on your Twilio console and select the active phone number used for your application. Next, scroll down to the Messaging segment and update the webhook URL for the field labeled *“A MESSAGE COMES IN”* as shown below:

![https://res.cloudinary.com/brianiyoha/image/upload/v1585526269/Articles%20sample/Group_19.png](https://res.cloudinary.com/brianiyoha/image/upload/v1585526269/Articles%20sample/Group_19.png)

**NOTE:** *The URL path should be prefixed with `/api`.*

## Testing the bot

Now that your webhook has been updated, you are now ready to test your application. To do this simply send a text with a country name to your active Twilio phone number and you should receive a response shortly after with the summary of the coronavirus cases in the country if the name is valid or an error message otherwise:

![https://res.cloudinary.com/brianiyoha/image/upload/v1585527097/Articles%20sample/messages.google_4.png](https://res.cloudinary.com/brianiyoha/image/upload/v1585527097/Articles%20sample/messages.google_4.png)

## Conclusion

Now that you have finished this tutorial, you have successfully built an SMS based bot. You have also learned how to respond to SMS sent to your Twilio phone number from a Laravel application. If you will like to take a look at the complete source code for this tutorial, you can find it on [Github](https://github.com/thecodearcher/covid19-sms-bot).

Remember to stay safe, stay indoors and always wash your hands and we will get through this together ❤️.

I’d love to answer any question(s) you might have concerning this tutorial. You can reach me via

- Email: [brian.iyoha@gmail.com](mailto:brian.iyoha@gmail.com)
- Twitter: [thecodearcher](https://twitter.com/thecodearcher)
- GitHub: [thecodearcher](https://github.com/thecodearcher)
