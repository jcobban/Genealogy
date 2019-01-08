/**
 * DbTool.java
 * 
 * Command line tool for performing updates on a
 * Legacy Family Tree database.
 */
package net.jamescobban.legacy;

import java.sql.*;
//import sun.jdbc.odbc.*;


/**
 * @author jcobban
 *
 */
public class DbTool {

	/**
	 * url
	 * 
	 * The Uniform Resource Locator of the database.
	 */
	private String			url;
	
	/**
	 * connection
	 * 
	 * A connection to the database server.
	 */
	private Connection		connection;
	
	/**
	 * odbcName
	 * 
	 * The name of the ODBC resource representing
	 * the database.
	 */
	static String	odbcName	= "legacy";	
	
	/**
	 * userid
	 * 
	 * The user identifier to use to log on to the
	 * database.
	 */
	static String	userid		= "";	
	
	/**
	 * password
	 * 
	 * The password to authenticate access to the database.
	 */
	static String	password	= "";
	
	/**
	 * DbTool.main
	 * 
	 * Initiate the class from the command line.
	 * 
	 * @param args
	 */
	public static void main(String[] args) {
		// create and instance of DbTool
		DbTool	instance	= new DbTool();
		
		// perform the utility function

		instance.splitPrefix("Blacksmith",
		 "");
		instance.splitPrefix("Boarding House",
		 "");
		instance.splitPrefix("Teacher",
		 "");
		instance.splitPrefix("Teamster",
		 "");
		instance.splitPrefix("Telegraph",
		 "");
		instance.splitPrefix("Telephone",
		 "");
		instance.splitPrefix("Thrasher",
		 "");
		instance.splitPrefix("Thresher",
		 "");

	}

	/**
	 * DbTool.DbTool
	 *
	 * Constructor.
	 */
	public DbTool() {
		super();
		
		// ensure the driver class is loaded
		try
		{
			Class.forName("sun.jdbc.odbc.JdbcOdbcDriver");
		}
		catch(Exception e)
		{
			e.printStackTrace();
		}

		// determine the URL for accessing the database
		this.url	= "jdbc:odbc:" + DbTool.odbcName;
		
		try
		{
			this.connection	= DriverManager.getConnection(this.url);
		}
		catch(SQLException e)
		{
			e.printStackTrace();
		}
		
	}		// DbTool.DbTool constructor

	/**
	 * DbTool.splitPrefix
	 * 
	 * Correct badly formed occupation locations where the
	 * location starts with an occupation name.  The badly
	 * formed location is split into separate occupation
	 * description and location fields.  If necessary the
	 * location master table is updated to include any new
	 * locations.  Optionally the first part of the occupation
	 * description is updated.
	 *
	 * @param badPrefix	the initial portion of a location
	 * 					value that is actually the beginning
	 * 					of an occupation description.  The
	 * 					method searches for all existing
	 * 					locations that start with this String.
	 * @param newPrefix	if this is a non-empty string then when
	 * 					creating the new occupation description
	 * 					this value replaces the value of badPrefix.
	 */
	public void splitPrefix(String badPrefix,
						    String newPrefix)
	{
		System.out.println("splitPrefix(\"" + badPrefix +
						   "\", \"" + newPrefix + "\")");
		if (this.connection != null)
		{
			try {
				// Phase 1:  Create any needed locations
				PreparedStatement	stmt	= connection.prepareStatement(
						"SELECT IDLR, Location FROM tblLR WHERE LEFT(Location, ?)=? ORDER BY Location",
						ResultSet.TYPE_SCROLL_INSENSITIVE,
						ResultSet.CONCUR_READ_ONLY);
				ResultSet			rs;
				PreparedStatement	stmt2	= connection.prepareStatement(
						"SELECT IDLR, Location FROM tblLR WHERE Location=?");
				ResultSet			rs2;
				PreparedStatement	instStmt	= connection.prepareStatement(
					"INSERT INTO tblLR (FSPlaceId, Location, Used, SortedLocation, ShortName, Preposition, Notes) " +
				    "VALUES ( ?, ?, ?, ?, ?, ?, ?)");
				PreparedStatement	eventStmt	= connection.prepareStatement(
					"UPDATE tblER SET Description=?, IDLREvent=? WHERE IDLREvent=?" );
				
				// fields used for interpreting the results
				int			idlr;			// unique numeric identifier of location
				int			oldIdlr;		// identifier of old location
				int			newIdlr;		// identifier of new location
				int			dlm;			// start of location portion
				String 		location;		// location text from database
				String		occupation;		// occupation portion of improperly formatted locations
				String		mainLoc;		// actual location portion of improperly formatted locations
				String		padding		= "                                                  ";

				// construct the main Query.  This obtains a list of all locations that
				// start with the specified prefix.
		
				
				System.out.println("Phase 1:");
				stmt.setInt(1, badPrefix.length());
				stmt.setString(2, badPrefix);
				rs		= stmt.executeQuery();
				while(rs.next())
				{			// loop through improperly formatted locations
					location		= rs.getString(2);
					
					// determine where to split the string.
					// dlm is set to the offset of the comma delimiter
					// between the occupation description and the actual location
					// Note that the last character of badPrefix may be a comma
					// in which case that is the comma delimiter.
					dlm	= location.indexOf(",", badPrefix.length() - 1);
					if (dlm >= 0)
					{
						if (newPrefix.length() > 0)
							occupation	= newPrefix + location.substring(badPrefix.length(), dlm);
						else
							occupation	= location.substring(0, dlm);
						mainLoc		= location.substring(dlm + 2);
					}
					else
					{
						occupation	= location;
						mainLoc		= "";
					}
					System.out.println(occupation + padding.substring(0,Math.max(padding.length() - occupation.length(), 1)) +
							": " + mainLoc);
					
					// Determine whether there is already a location record matching
					// the location portion of the improperly formatted value
					stmt2.setString(1, mainLoc);
					rs2		= stmt2.executeQuery();
					if (rs2.next())
					{		// got a row
						idlr	= rs2.getInt(1);
						System.out.println("Location matches IDLR=" + idlr);
					}		// got a row
					else
					{		// no match, need to insert new location record
						System.out.println("Need to insert new location record");
					    instStmt.setString(1, mainLoc);
					    instStmt.setString(2, mainLoc);
					    instStmt.setInt(3, 1);			// mark as used
					    instStmt.setString(4, mainLoc);
					    instStmt.setString(5, mainLoc);
					    instStmt.setString(6, "at");
					    instStmt.setString(7, "");
					    int count	= instStmt.executeUpdate();
					    System.out.println("Inserted " + count + " lines");
					    if (count >= 1)
					    {		// new location inserted
					    	// query to get new value of IDLR
							rs2		= stmt2.executeQuery();
							if (rs2.next())
							{		// got a row
								idlr	= rs2.getInt(1);
								System.out.println("Location matches IDLR=" + idlr);
							}		// got a row
							else
							{		// no match!
								System.out.println("Unable to retrieve new value of IDLR!");
								idlr	= 0;
							}		// no match!
					    }		// new location inserted
					}		// no match, need to insert new location record
				}			// loop through all matching locations
				
				// Phase 2: Update Events matching the old locations
				// Traverse the main selection result set again
				rs.beforeFirst();
				while(rs.next())
				{			// loop through all matching locations
					oldIdlr			= rs.getInt(1);
					location		= rs.getString(2);
					
					// determine where to split the string.
					// dlm is set to the offset of the comma delimiter
					// between the occupation description and the actual location
					// Note that the last character of badPrefix may be a comma
					// in which case that is the comma delimiter.
					dlm	= location.indexOf(",", badPrefix.length() - 1);
					if (dlm >= 0)
					{
						if (newPrefix.length() > 0)
							occupation	= newPrefix + location.substring(badPrefix.length(), dlm);
						else
							occupation	= location.substring(0, dlm);
						mainLoc		= location.substring(dlm + 2);
					}
					else
					{
						occupation	= location;
						mainLoc		= "";
					}
				
					System.out.println(occupation + padding.substring(0,Math.max(padding.length() - occupation.length(), 1)) +
							": " + mainLoc);
					
					// query to get the IDLR value for the corrected location
					stmt2.setString(1, mainLoc);
					rs2		= stmt2.executeQuery();
					if (rs2.next())
					{		// got a row
						newIdlr	= rs2.getInt(1);
						System.out.println("Issue: UPDATE tblER SET Description='" +
									occupation + "', IDLREvent=" + newIdlr + " WHERE IDLREvent=" + oldIdlr);
						eventStmt.setString(1, occupation);	// Description set to occupation
						eventStmt.setInt(2, newIdlr);
						eventStmt.setInt(3, oldIdlr);
					    int count	= eventStmt.executeUpdate();
					    System.out.println("Updated " + count + " lines");
					}		// got a row
					else
					{		// no match!
						System.out.println("Unable to retrieve new value of IDLR!");
						idlr	= 0;
					}		// no match!
				}			// loop through all matching locations
				rs.close();
				
				// release resources
				stmt2.close();
				instStmt.close();
				eventStmt.close();
			}
			catch(SQLException e)
			{
				e.printStackTrace();
			}
		}
		else
		{
			System.out.println("Unable to obtain a connection");
		}
	}
	/* (non-Javadoc)
	 * @see java.lang.Object#toString()
	 */
	@Override
	public String toString() {
		return "DbTool " +
				"[url=" + url + "]" +
			   	"[connection=" + connection + "]";
	}
	

}
