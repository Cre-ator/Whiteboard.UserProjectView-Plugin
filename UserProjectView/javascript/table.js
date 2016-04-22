function row_view( category )
{
    for ( var i = 0; i < document.getElementsByName( category ).length; i++ )
    {
        if ( document.getElementsByName( category )[i] )
        {
            var actualVisibility = document.getElementsByName( category )[i].style.visibility;
            if ( actualVisibility == '' || actualVisibility == 'visible' )
            {
                document.getElementsByName( category )[i].style.visibility = "hidden";
                document.getElementsByName( category )[i].style.display = "none";
            }
            else
            {
                document.getElementsByName( category )[i].style.visibility = "visible";
                document.getElementsByName( category )[i].style.display = "";
            }
        }
    }
}