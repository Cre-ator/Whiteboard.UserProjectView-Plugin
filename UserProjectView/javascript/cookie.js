/**
 * Created by schwarz on 20.05.2016.
 */

/**
 * Funktion: get_cookie()
 * holt cookie-Wert
 * Parameter:cookie-Name
 * Rückgabewert: cookie-Wert/ false
 **/

function get_cookie ( cookieName )
{
    strValue = false;

    if ( strCookie = document.cookie )
    {
        if ( arrCookie = strCookie.match ( new RegExp ( cookieName + '=([^;]*)', 'g' ) ) )
        {
            strValue = RegExp.$1;
        }
    }
    return (strValue);
}

/**
 * Funktion:set_cookie()
 * setzt cookie
 * Parameter: cookie-Name,cookie-Wert,Haltbarkeit in Tagen
 * Rückgabewert: true/false
 **/

function set_cookie ( cookieName, cookieValue, intDays )
{
    if ( !is_cookie_enabled () )
    {
        return false;
    }

    objNow = new Date ();
    strExp = new Date ( objNow.getTime () + ( intDays * 86400000) );
    document.cookie = cookieName + '=' +
        cookieValue + ';expires=' +
        strExp.toGMTString () + ';';
    return true;
}

/**
 * Funktion:delete_cookie()
 * Löscht cookie
 * Parameter: cookie-Name
 * Rückgabewert: true/false
 **/

function delete_cookie ( cookieName )
{
    if ( document.cookie )
    {
        document.cookie = cookieName + '=' +
            get_cookie ( cookieName ) +
            ';expires=Thu, 01-Jan-1970 00:00:01 GMT;';
        return true;
    }
    return false;
}

/**
 * Funktion is_cookie_enabled()
 * prüft ob cookies erlaubt sind
 * Parameter: nix
 * Rückgabewert: true/false
 **/

function is_cookie_enabled ()
{
    if ( typeof navigator.cookieEnabled != 'undefined' )
    {
        return navigator.cookieEnabled;
    }

    set_cookie ( 'testcookie', 'testwert', 1 );

    if ( !document.cookie )
    {
        return false;
    }

    delete_cookie ( 'testcookie' );
    return true;
}
