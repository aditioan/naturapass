$(".publication_publicationcolor").find('option[value=""]').attr("data-color", "#333");
$(".publication_publicationcolor").colorselector();
$(".edit_publication_publicationcolor").colorselector();
$(".edit_media_publication_publicationcolor").colorselector();
function setColorSelector(val)
{
    $(".publication_publicationcolor").colorselector("setValue", val);
}
function setEditColorSelector(val)
{
    $(".edit_publication_publicationcolor").colorselector("setValue", val);
}
function setEditMediaColorSelector(val)
{
    $(".edit_media_publication_publicationcolor").colorselector("setValue", val);
}