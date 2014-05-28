// /**
//  * 	License: www.workbooks.com/mit_license
//  * 	Last commit $Id$
//  *
//  */
using System;
using System.Collections.Generic;
using WorkbooksApiApplication;
using System.IO;
using System.Text;
using System.Diagnostics;

namespace ApiWrapper
{
	public class UploadFilesExample
	{
		static WorkbooksApi  workbooks = null;
		static TestLoginHelper login = null;


		public static void Main() {
      login = new TestLoginHelper();
			//			consoleListener.WriteLine  ("Logged in");
			workbooks = login.testLogin ();
			//consoleListener.WriteLine ("Obtained the workbooks object");

			UploadFilesExample uploadEx = new UploadFilesExample ();
			uploadEx.createOrganisation ();
		}

		/** Method which creates an organisation, creates a note related to it and then upload different types of files to the Notes
	 * */
		public void createOrganisation() {

			//Create a single organisation, to which we will attach a note.
			List<Dictionary<string, object>> objectIdLockVersion = null;
			List<Dictionary<string, object>> multipleOrganisations = new List<Dictionary<string, object>> ();
			Dictionary<string, object> oneOrganisation = new Dictionary<string, object> ();
			oneOrganisation.Add ("name", "Csharp Test Organisation");

			Dictionary<string, object> options = new Dictionary<string, object> ();
			options.Add ("content_type", "multipart/form-data");

			multipleOrganisations.Add (oneOrganisation);
			// Create the Organisation
			try {
				objectIdLockVersion = workbooks.idVersions (workbooks.assertCreate ("crm/organisations", multipleOrganisations, null, null));

			} catch (Exception e) {
				Console.WriteLine ("Exception while creating organisations:", new Object[] { e.Message });
				Console.Write (e.StackTrace);
			}
		

			//  Create a note associated with that organisation, to which we will attach files.
			Dictionary<string, object> createNote = new Dictionary<string, object> ();
			List<Dictionary<string, object>> multipleNotes = new List<Dictionary<string, object>> ();

			createNote.Add ("resource_id", ((Dictionary<string, object>)objectIdLockVersion [0]) ["id"]);
			createNote.Add ("resource_type", "Private::Crm::Organisation");
			createNote.Add ("subject", "Test Note");
			createNote.Add ("text", "This is the body of the test note. It is <i>HTML</i>");
			multipleNotes.Add (createNote);

			int note_id = 0;		
			try {
				objectIdLockVersion = workbooks.idVersions (workbooks.assertCreate ("notes", multipleNotes, null, null));
				note_id = (int )((Dictionary<string, object>)objectIdLockVersion [0]) ["id"];
			} catch (Exception e) {
				Console.WriteLine ("Error while creating the Note. ", new Object[] { e.Message });
				Console.WriteLine (e.StackTrace);
			}

			Dictionary<string, object> file1 = null;

			string[] fileNames = new string[] {"HindiLanguage.txt", "file.htm", "Байкал Бизнес Центр", "<OK> O'Reilly & Partners",  "park.jpg" };
			string[] fileTypes = new string[] { "text/plain","text/html", "text/plain", "text/plain", "image/jpeg" };
			object[] fileData = new object[] { "मुझे हिंदी नहीं समझ आती", "<b> A small fragment of HTML</b>", "экологически чистом районе города Солнечный. Включает  ... whatever that means.", "'象形字 指事字 会意字 / 會意字  假借字 形声字 / 形聲字 xíngshēngzì. By far.",
				System.Convert.FromBase64String ("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")
			};

			List<Dictionary<string, object>> varietyOfFiles = new List<Dictionary<string,object>> ();

			for(int i=0; i< fileNames.Length ; i++) {
				file1 = new Dictionary<string, object> ();
				file1.Add ("name", fileNames [i]);
				file1.Add ("type", fileTypes [i]);
				file1.Add ("data", fileData [i]);

				varietyOfFiles.Add (file1);
			}


			// Build the arraylist to upload the files
			List<Dictionary<string, object>> createUploadsList = new List<Dictionary<string, object>>();

			List<FileInfo> listOfTempFiles = new List<FileInfo> ();

			foreach (Dictionary<string, object> eachFile in varietyOfFiles) {
				FileInfo tempFile =null;
				// Create a temporary file from the data array.
				if (eachFile["type"].ToString().StartsWith("image")) { // If the file data is image data, then it is already converted into bytes.
					tempFile = writeToTempFile(eachFile["data"], true);
				} else {													// All other file data types should be sent as Strings
					tempFile = writeToTempFile(eachFile["data"], false);
				}

				Dictionary<string, object> tempUpload = new Dictionary<string, object> ();
				tempUpload.Add("tmp_name" , tempFile);
				tempUpload.Add("file_name", eachFile["name"]);
				tempUpload.Add("file_content_type", eachFile["type"]);

				Dictionary<string, object> createUploads = new Dictionary<string, object> ();

				createUploads.Add("resource_id", note_id);
				createUploads.Add("resource_type", "Private::Note");
				createUploads.Add("resource_attribute", "upload_files");
				createUploads.Add("upload_file[data]", tempUpload);

				// Add to arraylist
				createUploadsList.Add(createUploads);
				// put the tempFile back in the list to delete later
				listOfTempFiles.Add(tempFile);
			}

			// Upload the files to the Note
			try {
				workbooks.assertCreate("resource_upload_files", createUploadsList, null, options);
				Console.WriteLine("Files Uploaded");
			} catch(Exception e) {
				Console.WriteLine("Exception while uploading files",  e);
				Console.WriteLine (e.StackTrace);
				login.testExit(workbooks, 1);
			}		
			// Delete the temporary files created
			foreach (FileInfo fileName in listOfTempFiles) {
				fileName.Delete ();
				System.Diagnostics.Debug.WriteLine ("files are deleted");
			}

			login.testExit(workbooks, 0);

		}// method end

		/**
	 * Method to write the data passed to it in a temporary file
	 * @param fileData - The actual data passed in as an object (e.g. String, byte[])
	 * @param isInBytes - flag to mention if the fileData is in Bytes or not
	 * */
		public FileInfo writeToTempFile(Object fileData, bool isInBytes) {
			FileStream fop = null;
			byte[] contentInBytes = null;
			//	System.out.println("File data:" + fileData);
			String fileName = Path.GetTempFileName();

			FileInfo file = new FileInfo (fileName);

			try {
				//fileName = "upload-" + fileName;
				fop = file.OpenWrite ();

				// Write the bytes of the image directly into the file
				if (isInBytes) {
					contentInBytes = (byte[])fileData;
				} else { // Encode the characters with UTF-8
					contentInBytes =  (Encoding.UTF8.GetBytes(fileData.ToString()));
				}	
				fop.Write(contentInBytes, 0, contentInBytes.Length);
				fop.Flush();
				fop.Close();
				// get the content in bytes
//			System.out.println("size of file: " + file.length());
//			System.out.println("Done");

			} catch (IOException e) {
				Console.WriteLine(e.StackTrace);
			} finally {
				try {
					if (fop != null) {
						fop.Close();
					}
				} catch (IOException e) {
					Console.WriteLine(e.StackTrace);
				}
			}
			return file;
		}

	}
}

