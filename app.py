from getpass import getpass

from dlo_export import dlo

dlo = dlo.DLO()


def course_students(course):
    all_students = dlo.get_all_students_from_course(course['OrgUnit']['Id'])

    print("Alle studenten van " + course['OrgUnit']['Name'] + ":\n\n")
    for student in all_students:
        print(student['FirstName'] + " " + student['LastName'])


def course_selection():
    print("Jouw vakken:")

    my_courses = dlo.get_my_courses()
    i = 1
    for course in my_courses:
        print(str(i) + ". " + course['OrgUnit']['Name'])
        i = i + 1

    print("\n")
    course_index = int(input("Geef het nummer van het vak voor de lijst van studenten: "))

    course_students(my_courses[course_index - 1])


def user_login():
    username = input("HvA username (username@ad.hva.nl): ")
    password = getpass("HvA password: ")

    if dlo.login(username, password):
        course_selection()
    else:
        print("Login failed.")


def main():
    user_login()


if __name__ == '__main__':
    main()
