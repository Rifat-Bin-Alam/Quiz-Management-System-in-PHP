Quiz Management System

A web-based Quiz Management System developed using PHP and MySQL.
This system allows Admin, Teacher, and Student to manage and participate in quizzes efficiently.

All functionalities are implemented using .php files where each file contains integrated PHP, HTML, and CSS.

📌 Features
👨‍💼 Admin

Approve or reject teacher registrations

Delete teachers and students

Manage overall system users

👩‍🏫 Teacher

Register (requires admin approval)

Login after approval

Create quizzes

Add questions with multiple options

Set correct answers

View student results

Provide feedback/comments on student scores

👨‍🎓 Student

Register and login

View available quizzes

Attend quizzes

View results and feedback

🛠️ Technologies Used

Frontend: HTML, CSS

Backend: PHP

Database: MySQL (MariaDB)

Server: XAMPP / Localhost

Database Tool: phpMyAdmin

🗄️ Database Structure

Database Name: quiz_system

Tables:

admin

admin_id (Primary Key)

name

email (Unique)

password

teacher

teacher_id (Primary Key)

t_name

email (Unique)

password

phone_no

status (pending / approved)

student

student_id (Primary Key)

s_name

email (Unique)

password

phone_no

quiz

quiz_id (Primary Key)

teacher_id (Foreign Key)

quiz_name

date_created

question

question_id (Primary Key)

quiz_id (Foreign Key)

question_text

option1

option2

option3

correct_answer

score

score_id (Primary Key)

student_id (Foreign Key)

quiz_id (Foreign Key)

score_point

total_point

date_created

feedback

🔗 Database Relationships

A Teacher can create multiple quizzes.

A Quiz can have multiple questions.

A Student can attend multiple quizzes.

Each quiz attempt is recorded in the score table.

Admin controls teacher approval.

🚀 Installation Guide
1️⃣ Clone the Repository
git clone https://github.com/Rifat-Bin-Alam/Quiz-Management-System-in-PHP

2️⃣ Move Project Folder

Move the project folder to:

xampp/htdocs/

3️⃣ Import Database

Open phpMyAdmin

Create a new database named quiz_system

Click Import

Upload the provided .sql file

Click Go

4️⃣ Run the Project

Open your browser and go to:

http://localhost/quiz-management-system/

🔐 Default Sample Accounts (From SQL Dump)

You can use existing accounts from the database dump:

Admin

Email: rifat5566123@gmail.com

Password: 123456

Teacher (Approved)

Email: labonno@gmail.com

Password: 123456

Student

Email: r@gmail.com

Password: 123456

📊 System Workflow

Teacher registers → Status = Pending

Admin approves teacher

Teacher creates quiz and adds questions

Student attends quiz

Score is calculated automatically

Teacher can provide feedback

📷 Screenshots 
TBA




⚠️ Limitations

Passwords are stored in plain text (no hashing implemented)

Basic UI design

No timer functionality

No negative marking system

🔮 Future Improvements

Implement password hashing (bcrypt)

Add quiz timer

Add pagination

Improve UI with Bootstrap

Add question categories

Add result analytics

📌 Author

Developed by Rifat Bin Alam
CSE Student
