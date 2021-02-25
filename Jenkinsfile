pipeline {
    agent any

    stages {
        stage('cd') {
            steps {
                
                sh 'cd /home/dev/natura/NaturaPass'
                
            }
        }


        stage('touch') {
            steps {
               
                sh 'touch quynh.test'
               
            }
        }

        stage('pull') {
            steps {
                
                sh 'git pull origin master'
            }
        }
        }
}
