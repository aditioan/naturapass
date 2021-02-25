Upgrade to the new notification system
======================================

Update the database with the doctrine migrations commands
```
app/console doctrine:migrations:migrate
```

How to use
==========

If you want to create a new Notification, add a class to the NotificationBundle in the correct directory, depending of its use.
A few options have to configured prior to the notification to be used

```php
    protected $options = array(
        'route' => '',
        'type' => '',
        'multiple' => false,
        'persistable' => true,
        'socket' => array(
            'enabled' => true,
            'event_name' => 'api-notification:incoming'
        ),
        'push' => array(
            'enabled' => true,
            'silent' => false
        )
    );
```

- "route" define the Symfony2 route to generate a link for the notification
- "type" is the type of the notification.
- "multiple" define if the notification can override multiple old notifications and take their place
- "persistable" define if the notification will be persist to the database. The SocketOnly notifications have no use to be persisted
- "socket":
    - "enabled" define if the notification will be pushed to the Socket.io server
    - "event_name" define the event name sent to Socket.io server
- "push":
    - "enabled" define if the notification will be pushed to the users's devices
    - "silent" define if the notification will be a silent one
    

For the notifications that's need to be persisted, add the annotation "@ORM\Entity" on top of the class. 
The type has also to be declared in the AbstractNotification's DiscriminatorMap.


And for something else, see the uses in the classes :)