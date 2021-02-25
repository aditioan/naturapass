define([], function()
{
    return {
        defaultRoutePath: '/',
        routes: {
            '/': {
                dependencies: [
                    ''
                ]
            },
            '/users/profile/parameters': {
                dependencies: [
                    'controllers/ParametersController',
                    ''
                ]
            }
        }
    };
});