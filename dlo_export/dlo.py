import requests
import re
import json

class DLO:
    BRIGHTSPACE_PATH = "https://dlo.mijnhva.nl"
    SIGN_IN_PATH = "https://login.hva.nl/"
    session = requests.Session()

    def get_session(self):
        return self.session

    def make_request(self, path, type = 'get', data=None):
        if data is None:
            data = {}
        if type == 'get':
            request = self.get_session().get(path, data=data)
        elif type == 'post':
            request = self.get_session().post(path, data=data)
        else:
            print('Unknown type')
            return

        return request.text

        if request.status_code == 200:
            return request.text
        return False

    def login(self, username, password):
        rq = self.make_request(self.BRIGHTSPACE_PATH)

        if not rq:
            print("Error -> Cannot find homepage")
            return

        #print(rq)
        form_action = re.search("action=\"/(adfs/(.*))\"", rq)

        if not form_action:
            print("Error -> Cannot find form action url")
            return

        form_action_path = form_action.group(1)

        login_rq = self.make_request(self.SIGN_IN_PATH + form_action_path, 'post', {
            'UserName': username,
            'Password': password,
            'AuthMethod': 'FormsAuthentication'
        })

        surfconext_form_action = re.search("action=\"(.*?)\">", login_rq)
        SAMLResponse_form = re.search("name=\"SAMLResponse\" value=\"(.*?)\"", login_rq)

        if not SAMLResponse_form:
            return False

        login_rq_surfconext = self.make_request(surfconext_form_action.group(1), 'post', {
            'SAMLResponse': SAMLResponse_form.group(1)
        })

        dlo_sso_form_action = re.search("action=\"(.*?)\">", login_rq_surfconext)
        dlo_SAMLResponse_form = re.search("name=\"SAMLResponse\" value=\"(.*?)\"", login_rq_surfconext)
        dlo_RelayState_form = re.search("name=\"RelayState\" value=\"(.*?)\"", login_rq_surfconext)

        dlo_login = self.make_request(dlo_sso_form_action.group(1), 'post', {
            'SAMLResponse': dlo_SAMLResponse_form.group(1),
            'RelayState': dlo_RelayState_form.group(1)
        })

        dlo_sso_redirect = re.search("window.location='(.*?)'", re.sub(r'\s+', '', dlo_login))

        dlo_home = self.make_request(self.BRIGHTSPACE_PATH + dlo_sso_redirect.group(1))

        if re.search("Session.UserId", dlo_home):
            return True
        return False


    def get_my_courses(self, course_type_code = 'Course Offering'):
        groups_page = json.loads(self.make_request(self.BRIGHTSPACE_PATH + "/d2l/api/lp/1.10/enrollments/myenrollments/"))
        all_groups = groups_page['Items']

        while groups_page['PagingInfo']['Bookmark']:
            groups_page = json.loads(self.make_request(self.BRIGHTSPACE_PATH + "/d2l/api/lp/1.10/enrollments/myenrollments/?bookmark=" + groups_page['PagingInfo']['Bookmark']))
            if groups_page['Items']:
                all_groups = all_groups + groups_page['Items']

        my_courses = []
        for group in all_groups:
            if group['OrgUnit']['Type']['Code'] == course_type_code:
                my_courses.append(group)

        return my_courses

    def get_all_students_from_course(self, course_id = None):
        return json.loads(self.make_request(self.BRIGHTSPACE_PATH + "/d2l/api/le/1.10/" + str(course_id) + "/classlist/"))