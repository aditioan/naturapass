# API Changelog

## Upgrade to v2 from v1

### General
        
- When an entity has a photo to be displayed, the json key name is "photo", no matter which entity it is

### User

- Returning data of an user systematically returns your relation with him
    ```json
    {
        "user": {
            ...,
            "relation": {
                "mutualFriends": 3,
                "friendship": {
                    "state": 2,
                    "way": 2
                }
            
            }
        }
    ```

- The attribute "profilepicture" is now known as "photo" for every return of an user
    ```json
    {
        "user": {
            ...,
            "photo": "/img/interface/default-avatar.jpg"
        }
    }
    ```
    
### Publication

- The methods to like a publication and a publication comment have changed
    ```
    PUT /api/v1/publications/{publication_id}/like ==> POST /api/v2/publications/{publication_id}/likes
    PUT /api/v1/publications/{comment_id}/comment/like ==> POST /api/v2/publications/{comment_id}/comments/likes
    ```
    
- You can now get the users who liked a publication or a publication comment
    ```
    GET /api/v2/publications/{publication}/likes
    GET /api/v2/publications/{comment}/comment/likes
    ```

- The attributes unlikes and isUserUnlike were removed from Publication and from the publication comments

- The comments inside a publication are now returned like the following (where data is the array containing the comments):
    ```json
    {
        "publication": {
            ...,
            "comments": {
                "total": 5,
                "unloaded": 1,
                "data": [
                    ...
                ]
            }
        }
    ```

- You can now add a video publication asynchronously: the video is processed after the response is sent to the user and a notification will be emitted.
When the publication is processed after the response, you will receive a HTTP 202 ACCEPTED response.
The user will will receive a PublicationProcessedNotification with a link to the new publication

### Groups

- The retrieval of the subscribers has changed. 
It will now return by default all the subscribers of the group, no matter of their accesses. 
If you want to retrieve specific accesses, you have to pass an array containing the accesses.

    ```
    GET /api/v2/groups/{group_id}/subscribers
    GET /api/v2/groups/{group_id}/subscribers?accesses[]=3
    GET /api/v2/groups/{group_id}/subscribers?accesses[]=3&accesses[]=0
    ```
    
### Hunts

- The retrieval of the subscribers has changed the same of groups. An extra parameter permit to not retrieve the non-members of Naturapass

    ```
    GET /api/v2/hunts/{group_id}/subscribers
    GET /api/v2/hunts/{group_id}/subscribers?accesses[]=3
    GET /api/v2/hunts/{group_id}/subscribers?accesses[]=3&non_members=0
    ```