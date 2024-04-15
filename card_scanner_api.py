from flask import Flask, request, jsonify
import base64
import pytesseract
from PIL import Image
import phonenumbers
import re
from flask_cors import cross_origin
import spacy
import mysql.connector
from spacy.training.example import Example
import random
from spacy.tokens import DocBin
from datetime import datetime
import pytz

# OBJECTIVE
# The below instructions are followed because the train-model api is taking long time to train the model in server.
# so we are following this temporary solution by running the code in local and replacing the trained model in server.

# ****Instructions to train the model.******

# Download IntelliJ IDE with python by venv.
# Clone the code from git and open in IntelliJ IDE.
# Get the train.spacy file and trained_model folder from the server(ask server admins to get these files).
# Replace the existing train.spacy file and trained_model folder in Api_Files folder in this project directory
#                                                                            by the files,which is got from server.
# Open card_scanner_api.py file.
# import all libraries which we import in codes.
# Import Tesseract-OCR library and get the tesseract.exe.
# Extra libraries must need to be installed -> mysql-connector-python,Flask-Cors.
# Change the database and table config if needed.
# If needed, Add the sample records to the 'card_scanner_model_data_to_be_trained' table in your database Please follow the bellow query.
# If you need to add person name follow the below query.
# INSERT INTO `your_database_name`.`card_scanner_model_data_to_be_trained` (`names`, `type`, `model_trained`)
#                                                             VALUES ('name_to_be_trained', 'PERSON', '0');
# In the above query the vale of type and model_trained should not be changed for human name.
# If you need to add person's designation follow the below query.
# INSERT INTO `your_database_name`.`card_scanner_model_data_to_be_trained` (`names`, `type`, `model_trained`)
#                                                                       VALUES ('designation_to_be_trained', 'DES', '0');
# In the above query the vale of type and model_trained should not be changed for designation.
# After inserting the records run the app.py file.
# Hit this url postman http://localhost:8080/api/train-model.
# The API will take more time to train the model and update the records in table and give the success response.
# Now the mysql records are inserted to the trained_model folder and train.spacy file located inside the project structure.
# Now Give the updated trained_model folder(convert to zip) and train.spacy file to the server admin for
#                                                               replacing the model_trained folder and train.spacy file.


pytesseract.pytesseract.tesseract_cmd = r'C:/Program Files/Tesseract-OCR/tesseract.exe'
app = Flask(__name__)
# MySQL configuration
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': 'murali441',
    # 'database': 'cards_scanner'
}
database_name = 'cards_scanner'
table_name = 'card_scanner_model_data_to_be_trained'


@app.route('/ocrEXT', methods=['POST', 'OPTIONS'])
@cross_origin()
def base64_image():
    headers = request.headers
    auth = headers.get("X-Api-Key")
    if auth == 'ef229daa-d058-4dd4-9c93-24761842aec5':
        # data = request.json
        # encoded_image = data['image']
        # decoded_image = base64.b64decode(encoded_image)
        data = request.form['image']
        decoded_image = base64.b64decode(data)
        with open('image.jpg', 'wb') as f:
            f.write(decoded_image)
            filename = 'image.jpg'
            img = Image.open(filename)
            result = pytesseract.image_to_string(img)
            # print(result)

            # Extraction of phone number
            phone_numbers = []
            for match in phonenumbers.PhoneNumberMatcher(result, "IN"):
                start = match.start
                end = match.end
                no = result[start:end]
                phone_numbers.append(no)

            ##Extraction of Designation and Name from text
            # split the string into lines
            lines = result.splitlines()
            lines1 = [line.strip() for line in lines if line.strip()]
            text = []
            for i in range(len(lines)):
                designation = lines[i]
                text.append(designation)
            new_list = [x for x in text if x != '']

            lst1 = [x.upper() for x in new_list]
            # print("New List :", lst1)

            # website and company Extraction

            websites = []
            website = ""

            for item in lst1:
                if 'www.' in item.lower():
                    # print("Item :", item.replace(" ", ""))
                    new_item = item.replace(" ", "")
                    urls = re.findall(r'(www\.[^\s]+)', new_item, re.IGNORECASE)
                    websites.extend(urls)
            website = websites[0] if websites else ""
            print("Website :", website)

            email_pattern = r'\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b'
            # Search for email addresses in the OCR string
            mails = re.findall(email_pattern, result)
            email = mails[0] if mails else ""
            # Print the extracted email addresses
            print("Email :", email)

            domain_name = ""
            domain_names = []
            if website == "":
                company_name = email.split('@')[1].split('.')[-2] if email else ""
                if company_name == "gmail" or company_name == "yahoo" or company_name == "hotmail":
                    domain_name = ""
                else:
                    domain_name = company_name
            else:
                domain_name = website[4:]  # Extract from the 4th character onwards
                domain_name = website.split('.')[1]
            print("Company :", domain_name)
            domain_names.append(domain_name)

            nlp = spacy.load("./trained_model")

            # Extraction of Designation and Name
            splitedList = []
            for slist in lines1:
                splitedList.extend(slist.split())
            names_set = set()
            designation_set = set()
            training_data_path = "./train.spacy"
            train_data = list(spacy.tokens.DocBin().from_disk(training_data_path).get_docs(nlp.vocab))
            trained_names = []
            trained_designations = []
            for model_data in train_data:
                for ent in model_data.ents:
                    if ent.label_ == 'PERSON':
                        trained_names.append(ent.text)
                    if ent.label_ == 'DES':
                        trained_designations.append(ent.text)
            # print(trained_names)
            # Process each line using the loaded model
            for line in lines1:
                doc = nlp(line)
                # Merge all tokens into a single entity
                with doc.retokenize() as tokenizer:
                    tokenizer.merge(doc[0:len(doc)])
                for ent in doc.ents:
                    if ent.label_ == 'PERSON':
                        for i in range(0, len(trained_names)):
                            if trained_names[i].upper() in ent.text.upper():
                                names_set.add(ent.text)
                                # print(ent.text, ent.label_)
                    else:
                        for i in range(0, len(trained_names)):
                            if trained_names[i].upper() in ent.text.upper():
                                names_set.add(ent.text)
                                # print(ent.text, ent.label_)
                        small_designations = ["CEO", "C.E.O", "COO", "C.O.O", "CFO", "C.F.O", "CIO", "C.I.O", "CTO",
                                              "C.T.O", "CMO",
                                              "C.M.O",
                                              "CHRO", "C.H.R.O", "CDO", "C.D.O",
                                              "CPO", "C.P.O", "CGO", "C.G.O", "CCO", "C.C.O", "TL", "T.L", "vp", "v.p",
                                              "MD", "M.D",
                                              "GM", "G.M",
                                              "AVP", "A.V.P", "CRM", "C.R.M", "HR", "H.R", "HOS", "H.O.S",
                                              "BDM", "B.D.M", "AGM", "A.G.M", "BD", "B.D", "NPD", "N.P.D", "DGM",
                                              "D.G.M", "DCM",
                                              "D.C.M", "CPA",
                                              "C.P.A", "CFA", "C.F.A",
                                              "SPHR", "S.P.H.R", "CSCP", "C.S.C.P", "CBAP", "C.B.A.P", "CCNP",
                                              "C.C.N.P", "CISSP",
                                              "C.I.S.S.P",
                                              "MCSE", "M.C.S.E", "AWS", "A.W.S",
                                              "SVP", "S.V.P", "CLO", "C.L.O", "CNO", "C.N.O", "CAO", "C.A.O", "CBO",
                                              "C.B.O",
                                              "CCOO", "C.C.O.O",
                                              "SM", "S.M", "HRBP", "H.R.B.P", "PMP", "P.M.P", "PHR", "P.H.R", "MBA",
                                              "M.B.A"]
                        for small_designation in small_designations:
                            for k in range(0, len(splitedList)):
                                if small_designation == splitedList[k].upper():
                                    designation_set.add(splitedList[k])
                                    # print("Final Designation :", splitedList[k])
                                else:
                                    for j in range(0, len(trained_designations)):
                                        if trained_designations[j].upper() in ent.text.upper():
                                            designation_set.add(ent.text)
                                            # print(ent.text, ent.label_)
                        # print(ent.text, ent.label_)
            name_list = list(names_set)
            designation_list = list(designation_set)
            # print('Names : ', name_list)
            # print('Designations : ', designation_list)

            json_text = {"first_name": name_list,
                         "phone_number": phone_numbers,
                         "email_address": mails,
                         "website": websites,
                         "designation": designation_list,
                         "company": domain_names
                         }
            # print(json_text)

            return json_text
    else:
        return jsonify({"message": "ERROR: Unauthorized"}), 401


@app.route('/api/train-model', methods=['GET'])
def get_records():
    try:
        # Connect to MySQL
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor(dictionary=True)
        nlp = spacy.blank("en")
        # Execute SQL query
        # cursor.execute("SELECT * FROM cards_scanner.card_scanner_model_data_to_be_trained where model_trained = 0")
        cursor.execute('''SELECT * FROM {}.{} where model_trained = 0'''.format(database_name, table_name))
        additional_training_data = []
        # Fetch all rows
        records = cursor.fetchall()
        for record in records:
            if record['type'] == 'PERSON':
                names_data = [(record['names'] + " is a name.", [(0, len(record['names']), "PERSON")])]
                additional_training_data.extend(names_data)
                print('PERSON : ', record)
            elif record['type'] == 'DES':
                designation_data = [(record['names'] + " is a designation.", [(0, len(record['names']), "DES")])]
                additional_training_data.extend(designation_data)
                print('DES : ', record)
        print(additional_training_data)
        if len(additional_training_data) != 0:
            training_data_path = "./train.spacy"
            train_data = list(spacy.tokens.DocBin().from_disk(training_data_path).get_docs(nlp.vocab))

            # Append additional training data to the existing training data
            for text, annotations in additional_training_data:
                doc = nlp.make_doc(text)
                example = Example.from_dict(doc, {"entities": annotations})
                train_data.append(example.reference)

            # Save the updated training data
            db = spacy.tokens.DocBin(docs=train_data)
            db.to_disk(training_data_path)
            train_data = list(DocBin().from_disk("./train.spacy").get_docs(nlp.vocab))
            # Initialize the NER pipeline
            ner = nlp.add_pipe("ner")

            # Add PERSON label to the NER pipeline
            ner.add_label("PERSON")
            ner.add_label("DES")

            # Begin training the NER model
            optimizer = nlp.begin_training()
            for i in range(10):  # You may need to adjust the number of iterations
                random.shuffle(train_data)
                for doc in train_data:
                    # Create a training Example from tokens and annotations
                    tokens = [token.text for token in doc]
                    annotations = {"entities": [(ent.start_char, ent.end_char, ent.label_) for ent in doc.ents]}
                    example = Example.from_dict(nlp.make_doc(" ".join(tokens)), annotations)
                    nlp.update([example], sgd=optimizer)

            # Save the trained model to disk
            nlp.to_disk("./trained_model")

            for update in records:
                # Get current UTC datetime
                utc_now = datetime.utcnow()

                # Convert UTC to Indian Standard Time (IST)
                indian_timezone = pytz.timezone('Asia/Kolkata')
                ist_now = utc_now.replace(tzinfo=pytz.utc).astimezone(indian_timezone)

                # Format the datetime object to MySQL DATETIME format
                modified_on = ist_now.strftime('%Y-%m-%d %H:%M:%S')
                cursor.execute('''UPDATE {}.{} set model_trained = 1 , modified_on = '{}' where id = {}'''
                               .format(database_name, table_name, modified_on, update['id'], ))

                conn.commit()
            # Close cursor and connection
            cursor.close()
            conn.close()
            return (jsonify({'Records_added': len(additional_training_data), 'success': True}), 200,
                    {'ContentType': 'application/json'})
        else:
            # Return records as JSON
            return jsonify({'Records_added': 0, 'success': True}), 200, {'ContentType': 'application/json'}
    except mysql.connector.Error as e:
        return jsonify({'error': str(e)}), 500


if __name__ == '__main__':
    app.run(debug=True, port=8080)
