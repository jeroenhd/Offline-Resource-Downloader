# Offline Resource Downloader (such a catchy name!)
This is a piece of code that allows the user to download all CSS, JS and images in a mirrored website. It also tries to download archived copies from archive.org in case the resource is no longer available.

# FAQ

## How do I use this?
First you need to download the HTML of a webpage or website. You can use wget in recursive mode for this:
`wget -k -m -E -p -np -nc -R *reply*,*getrefs*,*sort*,*quotereply*,memberlist.php*,faq.php*,viewtopic.php*p=*,posting.php*,search.php*,ucp.php*,viewonline.php*,*sid*,*view=print*,*start=0* http://www.website.com/`
This command will download all web pages and all resources on the same domain required to view them.

However, wget does not provide a way to download resources from other websites without following links to other domains. That's where the PHP code comes in:
Then run `php download_images.php <directory containing HTML> <directory to put the resources in>` to download all external resources as well.
For example: the command `php download_images.php www.example.com resources` will store all resources in the directory `www.example.com` and put them in a folder called `resources`.

Warning: do not store resources in the same folder as the HTML files. The code will recurse into the downloaded resources and might try to download the entire internet!

## But... why?
I wrote this code because wget doesn't have an option to span hosts just to download images. I was mirroring a website and wanted to download the resources as well. So after downloading the HTML resources for the website I wrote this script to download the missing files for me.

## Your code is bad and you should feel bad
That is not a question. It's probably true though.
This was written as a quick, one-off script so it has not been designed or tested well. Feel free to send a pull request if you fix a problem while trying to use this code!
